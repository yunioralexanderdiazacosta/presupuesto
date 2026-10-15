<?php

namespace App\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Lógica global de acceso por módulos. Un módulo (config/modules.php) puede estar:
 *  - bloqueado para una empresa completa (team_disabled_modules)
 *  - fuera de la lista de módulos permitidos de un usuario restringido (users.restrict_modules + user_modules)
 *
 * Super Admin nunca se ve afectado. El mismo cálculo alimenta el middleware (403)
 * y el menú lateral (blockedUris), de modo que lo que se oculta es exactamente lo que se bloquea.
 */
class ModuleAccess
{
    /** Primer nivel del menú lateral => submenús (campo 'section' del catálogo) que agrupa. */
    public const GROUPS = [
        'Presupuestos' => ['Presupuestos', 'Presupuestar por CC'],
        'Gestión' => ['Gestión', 'Registro de Gastos', 'Salidas', 'Agroquímicos', 'Fertilizantes', 'Producción', 'Maquinarias'],
        'Remuneraciones' => ['Remuneraciones'],
        'Evaluación de Proyectos' => ['Planificación'],
        'Parámetros' => ['Parámetros'],
    ];

    /** Rutas disponibles para cualquier usuario autenticado, aunque esté restringido. */
    protected const ALWAYS_NAMES = [
        'home', 'home.index', 'logout',
        'system-guide', 'faq',
        'select.budget', 'select.seasons.save',
        'weather',
        'password.confirm', 'password.confirm.store', 'password.confirmation',
        'api.dollar-price.update',
        'api.products', 'api.parcels', 'api.cost-center-varieties', 'api.fuel-tanks',
        'api.suppliers.form-data',
        'levels2.get', 'levels3.get', 'levels4.get',
    ];

    protected const ALWAYS_PREFIXES = [
        'sidebar.',
        'profile', 'user-profile-information', 'user-password',
        'two-factor', 'other-browser-sessions', 'current-user',
    ];

    protected static ?array $catalog = null;

    public static function flush(): void
    {
        self::$catalog = null;
    }

    public static function catalog(): array
    {
        return self::$catalog ??= array_map(function (array $module) {
            $module['prefixes'] = $module['prefixes'] ?? [];
            $module['group'] = self::groupOf($module['section']);
            return $module;
        }, config('modules', []));
    }

    public static function catalogKeys(): array
    {
        return array_column(self::catalog(), 'key');
    }

    public static function groupOf(string $section): string
    {
        foreach (self::GROUPS as $group => $sections) {
            if (in_array($section, $sections, true)) {
                return $group;
            }
        }

        return $section;
    }

    /**
     * Catálogo agrupado por primer nivel del menú, sin los módulos indicados.
     * [['group' => 'Gestión', 'modules' => [['key', 'label', 'section'], ...]], ...]
     */
    public static function groupedCatalog(array $excludeKeys = []): array
    {
        $grouped = [];

        foreach (self::catalog() as $module) {
            if (in_array($module['key'], $excludeKeys, true)) {
                continue;
            }

            $grouped[$module['group']][] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'section' => $module['section'],
            ];
        }

        $ordered = [];
        foreach (array_keys(self::GROUPS) as $group) {
            if (isset($grouped[$group])) {
                $ordered[] = ['group' => $group, 'modules' => $grouped[$group]];
                unset($grouped[$group]);
            }
        }
        foreach ($grouped as $group => $modules) {
            $ordered[] = ['group' => $group, 'modules' => $modules];
        }

        return $ordered;
    }

    /**
     * Convierte 'agrochemicals.index' -> 'agrochemicals', 'cost.centers.index' -> 'cost.centers'.
     * Rutas sin punto (ej. 'dashboard') quedan igual.
     */
    public static function prefixOf(string $routeName): string
    {
        $parts = explode('.', $routeName);
        return count($parts) > 1 ? implode('.', array_slice($parts, 0, -1)) : $routeName;
    }

    /** Indica si una ruta pertenece al módulo identificado por $moduleKey. */
    public static function matches(string $moduleKey, ?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }

        if ($routeName === $moduleKey) {
            return true;
        }

        foreach (self::moduleEntry($moduleKey)['prefixes'] ?? [] as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        // Solo los módulos ".index" (o sin punto) cubren su familia de rutas. Una key como
        // 'outflows.dashboard' cubre únicamente su propia ruta, y las rutas que son key de otro módulo
        // (ej. 'outflows.dashboard' respecto de 'outflows.index') pertenecen a ese otro módulo.
        $base = match (true) {
            str_ends_with($moduleKey, '.index') => substr($moduleKey, 0, -5),
            !str_contains($moduleKey, '.') => $moduleKey . '.',
            default => null,
        };

        return $base !== null
            && str_starts_with($routeName, $base)
            && !in_array($routeName, self::catalogKeys(), true);
    }

    protected static function moduleEntry(string $moduleKey): array
    {
        foreach (self::catalog() as $module) {
            if ($module['key'] === $moduleKey) {
                return $module;
            }
        }

        return [];
    }

    public static function isAlwaysAllowed(string $routeName): bool
    {
        if (in_array($routeName, self::ALWAYS_NAMES, true)) {
            return true;
        }

        foreach (self::ALWAYS_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Bloqueo por empresa: algún módulo deshabilitado para el team incluye la ruta. */
    public static function isBlocked(?Team $team, ?string $routeName): bool
    {
        if (!$team || !$routeName) {
            return false;
        }

        $disabledKeys = $team->relationLoaded('disabledModules')
            ? $team->disabledModules->pluck('module_key')
            : $team->disabledModules()->pluck('module_key');

        return $disabledKeys->contains(fn ($key) => self::matches($key, $routeName));
    }

    /** Bloqueo por usuario: restringido y la ruta no pertenece a ninguno de sus módulos permitidos. */
    public static function isBlockedForRestrictedUser(User $user, ?string $routeName): bool
    {
        if (!$user->restrict_modules) {
            return false;
        }

        if (!$routeName) {
            return true;
        }

        if (self::isAlwaysAllowed($routeName)) {
            return false;
        }

        foreach ($user->allowedModuleKeys() as $key) {
            if (self::matches($key, $routeName)) {
                return false;
            }
        }

        return true;
    }

    public static function isBlockedFor(User $user, ?string $routeName): bool
    {
        if ($user->hasRole('Super Admin')) {
            return false;
        }

        return self::isBlocked($user->team, $routeName)
            || self::isBlockedForRestrictedUser($user, $routeName);
    }

    /**
     * Rutas GET sin parámetros bloqueadas para el usuario (ej. ['/payroll-reports']).
     * El menú lateral las usa para ocultar enlaces y grupos.
     */
    public static function blockedUris(User $user): array
    {
        return self::menuUris($user)['blocked'];
    }

    /**
     * Rutas GET siempre permitidas (FAQ, inicio, etc.). El menú no las cuenta al decidir
     * si un grupo quedó sin módulos visibles.
     */
    public static function neutralUris(User $user): array
    {
        return self::menuUris($user)['neutral'];
    }

    protected static function menuUris(User $user): array
    {
        $result = ['blocked' => [], 'neutral' => []];

        if ($user->hasRole('Super Admin')) {
            return $result;
        }

        $user->team?->loadMissing('disabledModules');

        $teamHasBlocks = $user->team && $user->team->disabledModules->isNotEmpty();
        if (!$teamHasBlocks && !$user->restrict_modules) {
            return $result;
        }

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (!$name || !in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }

            $uri = '/' . ltrim($route->uri(), '/');

            if (self::isAlwaysAllowed($name)) {
                $result['neutral'][] = $uri;
            } elseif (self::isBlockedFor($user, $name)) {
                $result['blocked'][] = $uri;
            }
        }

        return $result;
    }

    /**
     * Quién puede editar el acceso por módulos de un usuario: el Super Admin a cualquiera;
     * un Admin a los usuarios de su empresa (incluidos otros Admin), pero nunca al suyo propio.
     */
    public static function canManageModules(User $actor, User $target): bool
    {
        if ($target->hasRole('Super Admin')) {
            return false;
        }

        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        return $actor->hasRole('Admin')
            && $actor->id !== $target->id
            && $actor->team_id === $target->team_id;
    }

    /** Guarda la restricción de módulos del usuario si $actor tiene permiso para hacerlo. */
    public static function syncUserModules(User $actor, User $target, bool $restrict, array $keys): void
    {
        if (!self::canManageModules($actor, $target)) {
            return;
        }

        $keys = array_values(array_intersect(array_unique($keys), self::catalogKeys()));

        $target->modules()->delete();
        $target->unsetRelation('modules');

        $target->restrict_modules = $restrict && count($keys) > 0;
        $target->save();

        if ($target->restrict_modules) {
            $target->modules()->createMany(array_map(fn ($key) => ['module_key' => $key], $keys));
        }
    }
}

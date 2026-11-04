<?php

namespace App\Support;

use App\Models\BudgetVersion;
use App\Models\User;
use App\Services\BudgetCopyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve la versión de presupuesto activa del usuario para la temporada activa.
 *
 * - Solo los administradores pueden trabajar sobre una versión distinta de la oficial
 *   (se guarda en sesión como 'budget_version_id', igual que la temporada).
 * - Cualquier otro usuario, o una versión inválida/de otra temporada, cae en la oficial.
 */
class ActiveBudgetVersion
{
    public const SESSION_KEY = 'budget_version_id';

    private const CACHE_KEY = 'active_budget_version';

    public static function current(): ?BudgetVersion
    {
        $request = request();

        if ($request->attributes->has(self::CACHE_KEY)) {
            return $request->attributes->get(self::CACHE_KEY) ?: null;
        }

        $version = self::resolve();
        $request->attributes->set(self::CACHE_KEY, $version ?: false);

        return $version;
    }

    public static function id(): ?int
    {
        return self::current()?->id;
    }

    public static function set(BudgetVersion $version): void
    {
        session([self::SESSION_KEY => $version->id]);
        request()->attributes->remove(self::CACHE_KEY);
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
        request()->attributes->remove(self::CACHE_KEY);
    }

    public static function isAdmin(?User $user): bool
    {
        return $user && ($user->hasRole('Admin') || $user->hasRole('Super Admin'));
    }

    /**
     * Columna calificada con el alias real de la tabla ("agrochemicals as a" => "a.budget_version_id").
     *
     * @param  mixed  $from  Valor de $query->from (string con o sin alias, o expresión)
     */
    public static function qualifiedColumn($from, string $defaultTable): string
    {
        $alias = $defaultTable;

        if (is_string($from)) {
            $alias = preg_match('/\s+as\s+(\S+)$/i', trim($from), $matches) ? $matches[1] : trim($from);
        }

        return $alias . '.budget_version_id';
    }

    /**
     * Para consultas que no pasan por un modelo (DB::table): equivale a DB::table($table)
     * restringido a la versión activa. Ej: ActiveBudgetVersion::table('fields as f').
     */
    public static function table(string $table)
    {
        $query = DB::table($table);

        if ($versionId = self::id()) {
            $query->where(self::qualifiedColumn($table, $table), $versionId);
        }

        return $query;
    }

    /** Versiones de la temporada activa que pertenecen al equipo del usuario. */
    public static function forCurrentSeason()
    {
        $user = Auth::user();

        return BudgetVersion::where('season_id', session('season_id'))
            ->where('team_id', $user?->team_id);
    }

    /**
     * Para consultas que agregan directo sobre una tabla de items (sin pasar por las líneas):
     * equivale a DB::table($pivot) restringido a los items de las líneas de la versión activa.
     * Ej: ActiveBudgetVersion::pivot('fertilizer_items')->where('cost_center_id', $id)->count().
     */
    public static function pivot(string $pivotTable)
    {
        $query = DB::table($pivotTable);
        $versionId = self::id();

        if (!$versionId) {
            return $query;
        }

        $name = preg_replace('/\s+as\s+\S+$/i', '', trim($pivotTable));
        $config = collect(BudgetCopyService::TYPES)->firstWhere('pivot', $name);

        if (!$config) {
            throw new \InvalidArgumentException("{$pivotTable} no es una tabla de items de presupuesto.");
        }

        $parent = (new $config['model'])->getTable();
        $alias  = preg_match('/\s+as\s+(\S+)$/i', trim($pivotTable), $matches) ? $matches[1] : $name;

        return $query->whereIn("{$alias}.{$config['fk']}", function ($sub) use ($parent, $versionId) {
            $sub->select('id')->from($parent)->where('budget_version_id', $versionId);
        });
    }

    private static function resolve(): ?BudgetVersion
    {
        $user = Auth::user();

        if (!$user || !session('season_id')) {
            return null;
        }

        if (self::isAdmin($user) && session(self::SESSION_KEY)) {
            $selected = self::forCurrentSeason()->find(session(self::SESSION_KEY));
            if ($selected) {
                return $selected;
            }
        }

        return self::forCurrentSeason()->official()->first()
            ?? self::forCurrentSeason()->orderBy('id')->first();
    }
}

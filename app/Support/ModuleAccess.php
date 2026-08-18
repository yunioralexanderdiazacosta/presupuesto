<?php

namespace App\Support;

use App\Models\Team;
use Illuminate\Support\Str;

/**
 * Resuelve si una empresa (Team) tiene acceso a un módulo/ruta según config/modules.php
 * y la tabla team_disabled_modules.
 */
class ModuleAccess
{
    public static function catalog(): array
    {
        return config('modules', []);
    }

    public static function catalogKeys(): array
    {
        return array_column(self::catalog(), 'key');
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

    public static function isBlocked(?Team $team, ?string $routeName): bool
    {
        if (!$team || !$routeName) {
            return false;
        }

        $disabledKeys = $team->relationLoaded('disabledModules')
            ? $team->disabledModules->pluck('module_key')
            : $team->disabledModules()->pluck('module_key');

        if ($disabledKeys->isEmpty()) {
            return false;
        }

        $currentPrefix = self::prefixOf($routeName);

        return $disabledKeys->contains(fn ($key) => self::prefixOf($key) === $currentPrefix);
    }
}

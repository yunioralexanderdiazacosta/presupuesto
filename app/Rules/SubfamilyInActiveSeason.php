<?php

namespace App\Rules;

use App\Models\Level3;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * La subfamilia (Level3) debe pertenecer a un Level1 del equipo del usuario y de la temporada activa.
 * Evita guardar líneas apuntando al Level1 de otra temporada, que duplicaría barras/grupos en los reportes.
 */
class SubfamilyInActiveSeason implements ValidationRule
{
    public function __construct(private ?string $level1Name = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Level3::where('id', $value)
            ->whereHas('level2.level1', function ($q) {
                $q->where('team_id', Auth::user()?->team_id)
                    ->where('season_id', session('season_id'));
                if ($this->level1Name) {
                    $q->where('name', $this->level1Name);
                }
            })
            ->exists();

        if (!$exists) {
            $fail('La subfamilia seleccionada no pertenece a la temporada activa.');
        }
    }
}

<?php

namespace App\Traits;

use App\Models\Season;

trait CheckSeasonLocked
{
    /**
     * Aborta con 403 si la temporada activa está bloqueada.
     */
    protected function abortIfSeasonLocked(): void
    {
        $season = Season::find(session('season_id'));

        if ($season && $season->is_locked) {
            abort(403, 'La temporada está bloqueada. No se pueden realizar cambios.');
        }
    }
}

<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;
use App\Models\Season;

class InitializeSeason
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        // Primero buscar temporada marcada como predeterminada
        $seasonId = Season::where('team_id', $user->team_id)
            ->where('is_default', true)
            ->value('id');

        // Si no hay predeterminada, usar la última
        if (!$seasonId) {
            $seasonId = Season::where('team_id', $user->team_id)
                ->latest('id')
                ->value('id');
        }

        if ($seasonId) {
            Session::put('season_id', $seasonId);
        }
    }
}

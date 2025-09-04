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

        // Obtener la última temporada del equipo como predeterminada
        $seasonId = Season::where('team_id', $user->team_id)
            ->latest('id')
            ->value('id');

        if ($seasonId) {
            Session::put('season_id', $seasonId);
        }
    }
}

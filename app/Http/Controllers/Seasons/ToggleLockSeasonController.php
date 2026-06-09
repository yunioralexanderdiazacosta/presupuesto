<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Support\Facades\Auth;

class ToggleLockSeasonController extends Controller
{
    public function __invoke(Season $season)
    {
        // Solo puede bloquear/desbloquear temporadas del propio equipo
        if ($season->team_id !== Auth::user()->team_id) {
            abort(403);
        }

        $season->update(['is_locked' => !$season->is_locked]);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $team_id = $user->team_id;

        // Obtener el nombre de la temporada desde la sesión o la base de datos
        $season_name = session('season_name');
        if (!$season_name && $season_id) {
            $season = Season::find($season_id);
            $season_name = $season ? $season->name : 'Temporada Actual';
        }

        // Información básica para las cards
        $info = [
            'season_name' => $season_name ?? 'Temporada Actual',
            'team_name' => $user->team->name ?? 'Mi Equipo',
            'user_name' => $user->name,
        ];

        return Inertia::render('Home', compact('info'));
    }
}

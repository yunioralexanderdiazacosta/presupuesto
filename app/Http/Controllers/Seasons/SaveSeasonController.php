<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SaveSeasonController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->hasRole('Admin') && !$user->hasRole('Super Admin'),
            403,
            'Solo los administradores pueden cambiar la temporada.'
        );

        $request->validate([
            'season_id' => 'required'
        ]);

        // Obtener el nombre de la temporada
        $season = Season::find($request->season_id);
        
        session([
            'season_id' => $request->season_id,
            'season_name' => $season ? $season->name : 'Temporada',
            'season_color' => $season ? $season->color : null
        ]);

        // Retornar sin hacer nada - el frontend maneja el redirect
        return back();
    }
}

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
        \Log::info('═══ SAVE SEASON CONTROLLER START ═══', [
            'request_season_id' => $request->season_id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
        ]);

        $request->validate([
            'season_id' => 'required'
        ]);

        // Obtener el nombre de la temporada
        $season = Season::find($request->season_id);
        
        session([
            'season_id' => $request->season_id,
            'season_name' => $season ? $season->name : 'Temporada'
        ]);

        \Log::info('═══ SAVE SEASON CONTROLLER - SESSION SAVED ═══', [
            'session_season_id' => session('season_id'),
            'session_season_name' => session('season_name'),
            'session_has_season_id' => session()->has('season_id'),
            'all_session_keys' => array_keys(session()->all()),
        ]);

        // Retornar sin hacer nada - el frontend maneja el redirect
        return back();
    }
}

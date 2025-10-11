<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SaveSeasonController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'season_id' => 'required'
        ]);

        // Obtener el nombre de la temporada
        $season = Season::find($request->season_id);
        
        session([
            'season_id' => $request->season_id,
            'season_name' => $season ? $season->name : 'Temporada'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Support\Facades\Auth;

class SetDefaultSeasonController extends Controller
{
    public function __invoke(Season $season)
    {
        $user = Auth::user();

        if ($season->team_id !== $user->team_id) {
            return back()->with('error', 'No tienes permiso para modificar esta temporada.');
        }

        // Quitar default de todas las temporadas del equipo
        Season::where('team_id', $user->team_id)->update(['is_default' => false]);

        // Setear la seleccionada como default
        $season->update(['is_default' => true]);

        return back()->with('success', "Temporada \"{$season->name}\" establecida como predeterminada.");
    }
}

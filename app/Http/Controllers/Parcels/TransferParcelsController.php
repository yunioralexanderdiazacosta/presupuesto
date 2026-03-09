<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
use App\Models\Season;

class TransferParcelsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return back()->with('error', 'No hay una temporada activa seleccionada.');
        }

        // Obtener la temporada anterior más reciente del equipo
        $previousSeason = Season::where('team_id', $user->team_id)
            ->where('id', '<', $season_id)
            ->latest('id')
            ->first();

        if (!$previousSeason) {
            return back()->with('error', 'No se encontró una temporada anterior.');
        }

        $previousParcels = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $previousSeason->id)
            ->get();

        if ($previousParcels->isEmpty()) {
            return back()->with('error', 'La temporada anterior no tiene parcelas.');
        }

        // Obtener nombres de parcelas que ya existen en la temporada actual
        $existingNames = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->pluck('name')
            ->toArray();

        $copied = 0;
        foreach ($previousParcels as $parcel) {
            if (!in_array($parcel->name, $existingNames)) {
                Parcel::create([
                    'name'         => $parcel->name,
                    'observations' => $parcel->observations,
                    'season_id'    => $season_id,
                    'team_id'      => $user->team_id,
                ]);
                $copied++;
            }
        }

        if ($copied === 0) {
            return back()->with('info', 'Todas las parcelas de la temporada anterior ya existen.');
        }

        return back()->with('success', "Se traspasaron {$copied} parcelas desde {$previousSeason->name}.");
    }
}

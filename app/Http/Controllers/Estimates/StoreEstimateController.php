<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\StoreEstimateRequest;
use App\Models\Estimate;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class StoreEstimateController extends Controller
{
    public function __invoke(StoreEstimateRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $estimates = $request->all();
        Log::info('Datos recibidos en estimates.store:', $estimates);
        // Si el frontend envía un array de estimaciones
        $errores = [];
        foreach ($estimates as $estimate) {
            Log::info('Guardando estimate:', $estimate);
            $dataToSave = [
                'estimate_status_id' => $estimate['estimate_status_id'],
                'cost_center_id' => $estimate['cost_center_id'],
                'kilos_ha' => (int) $estimate['kilos_ha'],
                'observations' => $estimate['observations'] ?? '',
                'season_id' => $season_id,
                'team_id' => $user->team_id
            ];
            Log::info('Datos finales guardados en estimates:', $dataToSave);
            $existe = Estimate::where('estimate_status_id', $dataToSave['estimate_status_id'])
                ->where('cost_center_id', $dataToSave['cost_center_id'])
                ->where('season_id', $season_id)
                ->where('team_id', $user->team_id)
                ->exists();
            if ($existe) {
                $errores[] = "Ya existe una estimación con el estado '{$dataToSave['estimate_status_id']}' para el centro de costo ID {$dataToSave['cost_center_id']}";
                continue;
            }
            Estimate::create($dataToSave);
        }
        // Retornar respuesta para Inertia
        if (count($errores)) {
            return redirect()->back()->with('error', implode(' | ', $errores));
        }
        // Retornar respuesta para Inertia
        return redirect()->back()->with('success', 'Las estimaciones se han guardado correctamente.');
    }
}
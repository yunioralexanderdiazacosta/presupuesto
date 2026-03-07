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
        $errores = [];

        foreach ($estimates as $estimate) {
            $dataToSave = [
                'estimate_status_id' => $estimate['estimate_status_id'],
                'cost_center_variety_id' => $estimate['cost_center_variety_id'],
                'kilos_ha' => (int) $estimate['kilos_ha'],
                'observations' => $estimate['observations'] ?? '',
                'season_id' => $season_id,
                'team_id' => $user->team_id
            ];

            $existe = Estimate::where('estimate_status_id', $dataToSave['estimate_status_id'])
                ->where('cost_center_variety_id', $dataToSave['cost_center_variety_id'])
                ->where('season_id', $season_id)
                ->where('team_id', $user->team_id)
                ->exists();

            if ($existe) {
                $errores[] = "Ya existe una estimación para esa variedad y estado.";
                continue;
            }

            Estimate::create($dataToSave);
        }

        if (count($errores)) {
            $message = implode(' | ', $errores);
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }
        $successMsg = 'Las estimaciones se han guardado correctamente.';
        if ($request->wantsJson()) {
            return response()->json(['success' => $successMsg]);
        }
        return redirect()->back()->with('success', $successMsg);
    }
}
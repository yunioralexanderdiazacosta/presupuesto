<?php

namespace App\Http\Controllers\CostCenterVarieties;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterVarietyRequest;
use App\Models\CostCenter;
use App\Models\CostCenterVariety;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use App\Traits\CheckSeasonLocked;

class StoreCostCenterVarietyController extends Controller
{
    public function __invoke(FormCostCenterVarietyRequest $request)
    {
        $user      = Auth::user();
        $season_id = session('season_id');

        // Validar que la suma de superficies no supere la del cuartel
        $costCenter  = CostCenter::findOrFail($request->cost_center_id);
        $existingSum = CostCenterVariety::where('cost_center_id', $request->cost_center_id)
            ->where('season_id', $season_id)
            ->sum('surface');
        $newTotal = round($existingSum + $request->surface, 4);
        if ($newTotal > $costCenter->surface) {
            throw ValidationException::withMessages([
                'surface' => "La suma de superficies ({$newTotal} ha) supera la superficie total del cuartel ({$costCenter->surface} ha).",
            ]);
        }

        CostCenterVariety::create([
            'cost_center_id'       => $request->cost_center_id,
            'season_id'            => $season_id,
            'variety_id'           => $request->variety_id,
            'fruit_id'             => $request->fruit_id,
            'rootstock_id'         => $request->rootstock_id ?: null,
            'development_state_id' => $request->development_state_id ?: null,
            'surface'              => $request->surface,
            'year_plantation'      => $request->year_plantation ?: null,
            'observations'         => $request->observations,
            'team_id'              => $user->team_id,
        ]);
    }
}

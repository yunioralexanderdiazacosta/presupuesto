<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterRequest;
use App\Models\CostCenter;

class StoreCostCenterController extends Controller
{
    public function __invoke(FormCostCenterRequest $request)
    {
        $season_id = session('season_id');

        CostCenter::create([
            'name' => $request->name,
            'surface' => $request->surface,
            'observations' => $request->observations,
            'season_id' => $season_id,
            'fruit_id' => $request->fruit_id,
            'variety_id' => $request->variety_id,
            'parcel_id' => $request->parcel_id,
            'development_state_id' => $request->development_state_id,
            'year_plantation' => $request->year_plantation,
            'status' => $request->status ?? false
        ]);
    }
}

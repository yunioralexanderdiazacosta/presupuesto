<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterRequest;
use App\Models\CostCenter;
use App\Traits\CheckSeasonLocked;

class StoreCostCenterController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(FormCostCenterRequest $request)
    {
        $this->abortIfSeasonLocked();
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
            'company_reason_id' => $request->company_reason_id,
            'status' => $request->status ?? false,
            'branch_id' => $request->branch_id ?: null,
        ]);
    }
}

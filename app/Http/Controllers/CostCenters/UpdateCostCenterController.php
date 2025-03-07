<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterRequest;
use App\Models\CostCenter;


class UpdateCostCenterController extends Controller
{
    public function __invoke(CostCenter $costCenter, FormCostCenterRequest $request)
    {
        $costCenter->name = $request->name;
        $costCenter->surface = $request->surface;
        $costCenter->observations=$request->observations;
        $costCenter->fruit_id = $request->fruit_id;
        $costCenter->variety_id = $request->variety_id;
        $costCenter->parcel_id = $request->parcel_id;
        $costCenter->development_state_id = $request->development_state_id;
        $costCenter->year_plantation = $request->year_plantation;
        $costCenter->status = $request->status ?? false;
        $costCenter->save();
    }
}

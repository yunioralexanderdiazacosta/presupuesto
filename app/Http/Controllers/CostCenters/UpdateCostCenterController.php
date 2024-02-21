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
        $costCenter->budget_id = $request->budget_id;
        $costCenter->save();
    }
}

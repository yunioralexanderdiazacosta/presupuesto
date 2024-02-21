<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterRequest;
use App\Models\CostCenter;

class StoreCostCenterController extends Controller
{
    public function __invoke(FormCostCenterRequest $request)
    {
        CostCenter::create([
            'name' => $request->name,
            'surface' => $request->surface,
            'budget_id' => $request->budget_id
        ]);
    }
}

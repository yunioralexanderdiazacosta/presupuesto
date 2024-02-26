<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCostCenterRequest;
use App\Models\CostCenter;

class StoreCostCenterController extends Controller
{
    public function __invoke(FormCostCenterRequest $request)
    {
        $budget_id = session('budget_id');

        CostCenter::create([
            'name' => $request->name,
            'surface' => $request->surface,
            'observations' => $request->observations,
            'budget_id' => $budget_id
        ]);
    }
}

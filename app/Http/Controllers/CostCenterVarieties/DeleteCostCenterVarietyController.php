<?php

namespace App\Http\Controllers\CostCenterVarieties;

use App\Http\Controllers\Controller;
use App\Models\CostCenterVariety;

class DeleteCostCenterVarietyController extends Controller
{
    public function __invoke(CostCenterVariety $costCenterVariety)
    {
        $costCenterVariety->delete();
    }
}

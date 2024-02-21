<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CostCenter;

class DeleteCostCenterController extends Controller
{
    public function __invoke(CostCenter $costCenter)
    {
        $costCenter->delete();
    }
}

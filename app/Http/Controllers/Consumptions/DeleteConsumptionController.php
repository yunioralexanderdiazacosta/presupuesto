<?php

namespace App\Http\Controllers\Consumptions;

use App\Http\Controllers\Controller;
use App\Models\Consumption;

class DeleteConsumptionController extends Controller
{
    public function __invoke(Consumption $consumption)
    {
    $consumption->delete();
    return response()->noContent();
    }
}

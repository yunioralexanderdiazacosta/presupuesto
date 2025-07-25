<?php

namespace App\Http\Controllers\Harvests;

use App\Http\Controllers\Controller;
use App\Models\Harvest;

class DeleteHarvestController extends Controller
{
    public function __invoke(Harvest $harvest)
    {
        $harvest->items()->detach();
        $harvest->delete();
    }
}


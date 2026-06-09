<?php

namespace App\Http\Controllers\Harvests;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Traits\CheckSeasonLocked;

class DeleteHarvestController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Harvest $harvest)
    {
        $this->abortIfSeasonLocked();
        $harvest->items()->detach();
        $harvest->delete();
    }
}


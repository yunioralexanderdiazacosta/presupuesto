<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fertilizer;
use App\Traits\CheckSeasonLocked;

class DeleteFertilizerController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Fertilizer $fertilizer)
    {
        $this->abortIfSeasonLocked();
        $fertilizer->items()->detach();
        $fertilizer->delete();
    }
}

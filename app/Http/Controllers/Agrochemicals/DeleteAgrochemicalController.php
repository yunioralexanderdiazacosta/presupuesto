<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Models\Agrochemical;
use App\Traits\CheckSeasonLocked;

class DeleteAgrochemicalController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Agrochemical $agrochemical)
    {
        $this->abortIfSeasonLocked();
        $agrochemical->items()->detach();
        $agrochemical->delete();
    }
}

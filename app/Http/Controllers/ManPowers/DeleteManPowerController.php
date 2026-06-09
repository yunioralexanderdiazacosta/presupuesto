<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Traits\CheckSeasonLocked;

class DeleteManPowerController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(ManPower $manPower)
    {
        $this->abortIfSeasonLocked();
        $manPower->items()->detach();
        $manPower->delete();
    }
}

<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManPower;

class DeleteManPowerController extends Controller
{
    public function __invoke(ManPower $manPower)
    {
        $manPower->items()->detach();
        $manPower->delete();
    }
}

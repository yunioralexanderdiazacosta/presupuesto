<?php

namespace App\Http\Controllers\Rootstocks;

use App\Http\Controllers\Controller;
use App\Models\Rootstock;

class DeleteRootstockController extends Controller
{
    public function __invoke(Rootstock $rootstock)
    {
        $rootstock->delete();
    }
}

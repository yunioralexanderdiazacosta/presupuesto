<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Models\Agrochemical;

class DeleteAgrochemicalController extends Controller
{
    public function __invoke(Agrochemical $agrochemical)
    {
        $agrochemical->items()->detach();
        $agrochemical->delete();
    }
}

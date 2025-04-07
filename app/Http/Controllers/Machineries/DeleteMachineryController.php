<?php

namespace App\Http\Controllers\Machineries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machinery;

class DeleteMachineryController extends Controller
{
    public function __invoke(Machinery $machinery)
    {
        $machinery->delete();
    }
}

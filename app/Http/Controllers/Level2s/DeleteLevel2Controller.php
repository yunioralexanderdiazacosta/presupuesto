<?php

namespace App\Http\Controllers\Level2s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level2;

class DeleteLevel2Controller extends Controller
{
    public function __invoke(Level2 $level2)
    {
        $level2->delete();
    }
}

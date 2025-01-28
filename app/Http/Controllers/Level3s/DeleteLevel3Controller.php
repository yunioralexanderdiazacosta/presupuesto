<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level3;

class DeleteLevel3Controller extends Controller
{
    public function __invoke(Level3 $level3)
    {
        $level3->delete();
    }
}

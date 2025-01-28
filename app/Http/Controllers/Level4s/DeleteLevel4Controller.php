<?php

namespace App\Http\Controllers\Level4s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level4;

class DeleteLevel4Controller extends Controller
{
    public function __invoke(Level4 $level4)
    {
        $level4->delete();
    }
}

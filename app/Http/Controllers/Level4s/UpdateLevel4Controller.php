<?php

namespace App\Http\Controllers\Level4s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel4Request;
use App\Models\Level4;

class UpdateLevel4Controller extends Controller
{
    public function __invoke(Level4 $level4, FormLevel4Request $request)
    {
        $level4->name = $request->name;
        $level4->save();
    }
}

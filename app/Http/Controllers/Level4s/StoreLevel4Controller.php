<?php

namespace App\Http\Controllers\Level4s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel4Request;
use App\Models\Level4;

class StoreLevel4Controller extends Controller
{
    public function __invoke(FormLevel4Request $request)
    {
        Level4::create([
            'name' => $request->name,
            'level3_id' => $request->level_id
        ]);
    }
}

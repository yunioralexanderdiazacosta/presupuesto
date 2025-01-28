<?php

namespace App\Http\Controllers\Level2s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel2Request;
use App\Models\Level2;

class StoreLevel2Controller extends Controller
{
    public function __invoke(FormLevel2Request $request)
    {
        Level2::create([
            'name' => $request->name,
            'level1_id' => $request->level_id
        ]);
    }
}

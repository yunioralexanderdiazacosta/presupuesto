<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel3Request;
use App\Models\Level3;

class StoreLevel3Controller extends Controller
{
    public function __invoke(FormLevel3Request $request)
    {
        Level3::create([
            'name' => $request->name,
            'level2_id' => $request->level_id
        ]);
    }
}

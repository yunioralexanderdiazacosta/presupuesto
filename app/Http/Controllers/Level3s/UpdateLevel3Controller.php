<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel3Request;
use App\Models\Level3;

class UpdateLevel3Controller extends Controller
{
    public function __invoke(Level3 $level3, FormLevel3Request $request)
    {
        $level3->name = $request->name;
        $level3->save();
    }
}

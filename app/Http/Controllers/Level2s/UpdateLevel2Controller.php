<?php

namespace App\Http\Controllers\Level2s;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevel2Request;
use Illuminate\Http\Request;
use App\Models\Level2;

class UpdateLevel2Controller extends Controller
{
    public function __invoke(Level2 $level2, FormLevel2Request $request)
    {
        $level2->name = $request->name;
        $level2->save();
    }
}

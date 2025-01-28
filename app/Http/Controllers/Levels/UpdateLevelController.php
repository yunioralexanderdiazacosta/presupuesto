<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevelRequest;
use App\Models\Level1;

class UpdateLevelController extends Controller
{
    public function __invoke(Level1 $level, FormLevelRequest $request)
    {
        $level->name = $request->name;
        $level->save();
    }
}

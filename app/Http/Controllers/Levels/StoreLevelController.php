<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormLevelRequest;
use App\Models\Level1;

class StoreLevelController extends Controller
{
    public function __invoke(FormLevelRequest $request)
    {
        Level1::create([
            'name' => $request->name
        ]);
    }
}

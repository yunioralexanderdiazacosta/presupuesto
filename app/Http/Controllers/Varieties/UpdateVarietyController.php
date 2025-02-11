<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormVarietyRequest;
use App\Models\Variety;

class UpdateVarietyController extends Controller
{
    public function __invoke(Variety $variety, FormVarietyRequest $request)
    {
        $variety->name = $request->name;
        $variety->fruit_id = $request->fruit_id;
        $variety->observations = $request->observations;
        $variety->save();
    }
}

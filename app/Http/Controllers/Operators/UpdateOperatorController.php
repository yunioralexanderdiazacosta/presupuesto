<?php

namespace App\Http\Controllers\Operators;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormOperatorRequest;
use App\Models\Operator;

class UpdateOperatorController extends Controller
{
    public function __invoke(Operator $operator, FormOperatorRequest $request)
    {
        $operator->name     = $request->name;
        $operator->position = $request->position;
        $operator->save();
    }
}

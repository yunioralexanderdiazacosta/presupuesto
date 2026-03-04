<?php

namespace App\Http\Controllers\Operators;

use App\Http\Controllers\Controller;
use App\Models\Operator;

class DeleteOperatorController extends Controller
{
    public function __invoke(Operator $operator)
    {
        $operator->delete();
    }
}

<?php

namespace App\Http\Controllers\TypeMachineries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeMachinery;

class UpdateTypeMachineryController extends Controller
{
    public function __invoke(TypeMachinery $typeMachinery, Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $typeMachinery->name = $request->name;
        $typeMachinery->save();
    }
}

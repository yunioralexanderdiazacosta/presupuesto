<?php

namespace App\Http\Controllers\TypeMachineries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeMachinery;

class DeleteTypeMachineryController extends Controller
{
    public function __invoke(TypeMachinery $typeMachinery)
    {
        $typeMachinery->delete();
    }
}

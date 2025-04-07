<?php

namespace App\Http\Controllers\TypeMachineries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeMachinery;
use Illuminate\Support\Facades\Auth;

class StoreTypeMachineryController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required'
        ]);

        TypeMachinery::Create([
            'name' => $request->name,
            'team_id' => $user->team_id
        ]);
    }
}

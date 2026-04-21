<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TypeMachinery;
use Inertia\Inertia;

class TypeMachineriesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $type_machineries = TypeMachinery::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('TypeMachineries', compact('type_machineries'));
    }
}

<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\TypeMachinery;
use Inertia\Inertia;

class TypeMachineriesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $type_machineries = TypeMachinery::where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('TypeMachineries', compact('type_machineries'));
    }
}

<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Fruit;
use Inertia\Inertia;

class FruitsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $fruits = Fruit::where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Fruits', compact('fruits'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Level1;

class LevelsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $levels = Level1::where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Levels', compact('levels'));
    }
}

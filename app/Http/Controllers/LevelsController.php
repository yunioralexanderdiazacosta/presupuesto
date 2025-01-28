<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Level1;

class LevelsController extends Controller
{
    public function __invoke()
    {
        $levels = Level1::paginate(10);

        return Inertia::render('Levels', compact('levels'));
    }
}

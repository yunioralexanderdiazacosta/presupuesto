<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level2;
use App\Models\Level3;
use Inertia\Inertia;

class Level3Controller extends Controller
{
    public function __invoke(Level2 $level2)
    {
        $levels = Level3::where('level2_id', $level2->id)->paginate(10);

        return Inertia::render('Levels/Level3', compact('level2', 'levels'));
    }
}

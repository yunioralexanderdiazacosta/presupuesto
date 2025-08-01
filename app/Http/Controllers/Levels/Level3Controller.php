<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level2;
use App\Models\Level3;
use Inertia\Inertia;

class Level3Controller extends Controller
{
    public function __invoke(Level2 $level2, Request $request)
    {
        $term = $request->term ?? '';

        $levels = Level3::with('level2', 'level2.level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level2_id', $level2->id)->paginate(10);

        return Inertia::render('Levels/Level3', compact('level2', 'levels', 'term'));
    }
}

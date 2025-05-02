<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level1;
use App\Models\Level2;
use Inertia\Inertia;

class Level2Controller extends Controller
{
    public function __invoke(Level1 $level1, Request $request)
    {
        $term = $request->term ?? '';  

        $levels = Level2::with('level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level1_id', $level1->id)->paginate(10);

        return Inertia::render('Levels/Level2', compact('level1', 'levels', 'term'));
    }
}

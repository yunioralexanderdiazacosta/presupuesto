<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level3;
use App\Models\Level4;
use Inertia\Inertia;

class Level4Controller extends Controller
{
    public function __invoke(Level3 $level3, Request $request)
    {
        $term = $request->term ?? '';

        $levels = Level4::with('level3', 'level3.level2', 'level3.level2.level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level3_id', $level3->id)->paginate(10);

        return Inertia::render('Levels/Level4', compact('level3', 'levels', 'term'));
    }
}

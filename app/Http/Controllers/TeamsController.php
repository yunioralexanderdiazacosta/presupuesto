<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;


class TeamsController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = $request->term ?? '';

        $teams = User::with('team')
        ->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })
        ->orWhereHas('team', function($query) use ($term){
            $query->where('name', 'like', '%'.$term.'%');
        })
        ->whereHas('owner')
        ->role('Admin')
        ->paginate(10)->withQueryString();                                                                
        return Inertia::render('Teams', compact('teams', 'term'));
    }
}

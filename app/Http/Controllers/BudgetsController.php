<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Month;
use App\Models\Budget;
use App\Models\User;
use App\Models\Season;

class BudgetsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';

        $season_id = session('season_id');

        $budgets = Budget::with('season')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->where('season_id', $season_id)->paginate(10)->withQueryString();

        return Inertia::render('Budgets', compact('budgets', 'term'));
    }
}

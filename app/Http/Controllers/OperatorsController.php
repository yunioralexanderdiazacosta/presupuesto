<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Operator;
use App\Models\Branch;
use Inertia\Inertia;

class OperatorsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $operators = Operator::with('branch')
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($request->term, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->when($request->branch_id, fn($q, $id) => $q->where('branch_id', $id))
            ->orderBy('name', 'asc')
            ->paginate(2000)
            ->withQueryString();

        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Operators', [
            'operators' => $operators,
            'branches'  => $branches,
            'term'      => $request->term ?? '',
        ]);
    }
}

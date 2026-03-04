<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Operator;
use Inertia\Inertia;

class OperatorsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $operators = Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($request->term, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhere('position', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(2000)
            ->withQueryString();

        return Inertia::render('Operators', [
            'operators' => $operators,
            'term' => $request->term ?? '',
        ]);
    }
}

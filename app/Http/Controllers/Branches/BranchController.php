<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $term = $request->term ?? '';

        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->when($term, fn($q) => $q->where('name', 'like', '%' . $term . '%'))
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('Branches', compact('branches', 'term'));
    }
}

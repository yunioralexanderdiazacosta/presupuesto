<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Grouping;

class GroupingsController extends Controller
{
    /**
     * Display a listing of groupings.
     */
    public function __invoke(Request $request)
    {
        $term = $request->term ?? '';

        $groupings = Grouping::with('costCenters')
            ->when($term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->paginate(10);

        return Inertia::render('Groupings', compact('groupings', 'term'));
    }
}

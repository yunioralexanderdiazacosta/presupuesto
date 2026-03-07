<?php

namespace App\Http\Controllers\Rootstocks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Rootstock;
use Inertia\Inertia;

class RootstockController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $term = $request->term ?? '';

        $rootstocks = Rootstock::where('team_id', $user->team_id)
            ->when($term, fn($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(100);

        return Inertia::render('Rootstocks', compact('rootstocks', 'term'));
    }
}

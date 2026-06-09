<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreProjectEvaluationController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'target_margin' => 'required|numeric|min:0|max:100',
        ]);

        $user = Auth::user();

        ProjectEvaluation::create([
            'team_id'       => $user->team_id,
            'name'          => $request->name,
            'description'   => $request->description,
            'target_margin' => $request->target_margin,
        ]);

        return back()->with('success', 'Evaluación creada correctamente.');
    }
}

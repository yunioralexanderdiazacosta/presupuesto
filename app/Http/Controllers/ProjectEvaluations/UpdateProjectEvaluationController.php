<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateProjectEvaluationController extends Controller
{
    public function __invoke(Request $request, ProjectEvaluation $projectEvaluation)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);

        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'target_margin' => 'required|numeric|min:0|max:100',
        ]);

        $projectEvaluation->update([
            'name'          => $request->name,
            'description'   => $request->description,
            'target_margin' => $request->target_margin,
        ]);

        return back()->with('success', 'Evaluación actualizada correctamente.');
    }
}

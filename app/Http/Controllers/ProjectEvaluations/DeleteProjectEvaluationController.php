<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use Illuminate\Support\Facades\Auth;

class DeleteProjectEvaluationController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);

        $projectEvaluation->delete();

        return back()->with('success', 'Evaluación eliminada correctamente.');
    }
}

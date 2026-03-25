<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use App\Models\ProjectEvaluationRow;
use Illuminate\Support\Facades\Auth;

class DeleteProjectEvaluationRowController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation, ProjectEvaluationRow $row)
    {
        $user = Auth::user();
        abort_if($projectEvaluation->team_id !== $user->team_id, 403);
        abort_if($row->project_evaluation_id !== $projectEvaluation->id, 403);

        $row->delete();

        return back();
    }
}

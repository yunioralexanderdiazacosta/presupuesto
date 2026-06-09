<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use App\Models\ProjectEvaluationRow;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteProjectEvaluationRowController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation, ProjectEvaluationRow $row)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);
        abort_if((int) $row->project_evaluation_id !== (int) $projectEvaluation->id, 403);

        $row->delete();

        return back();
    }
}

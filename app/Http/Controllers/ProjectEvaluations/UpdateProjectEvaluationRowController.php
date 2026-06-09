<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use App\Models\ProjectEvaluationRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class UpdateProjectEvaluationRowController extends Controller
{
    public function __invoke(Request $request, ProjectEvaluation $projectEvaluation, ProjectEvaluationRow $row)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);
        abort_if((int) $row->project_evaluation_id !== (int) $projectEvaluation->id, 403);

        $data = $request->validate([
            'variety_id'     => 'required|integer|exists:varieties,id',
            'week'           => 'required|integer|min:1|max:53',
            'hectares'       => 'required|numeric|min:0',
            'kg_pessimistic' => 'required|numeric|min:0',
            'kg_base'        => 'required|numeric|min:0',
            'kg_optimistic'  => 'required|numeric|min:0',
        ]);

        $row->update($data);

        return back();
    }
}

<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreBulkProjectEvaluationRowsController extends Controller
{
    public function __invoke(Request $request, ProjectEvaluation $projectEvaluation)
    {
        $user = Auth::user();
        abort_if($projectEvaluation->team_id !== $user->team_id, 403);

        $request->validate([
            'rows'                  => 'required|array|min:1',
            'rows.*.variety_id'     => 'required|integer|exists:varieties,id',
            'rows.*.week'           => 'required|integer|min:1|max:53',
            'rows.*.hectares'       => 'required|numeric|min:0',
            'rows.*.kg_pessimistic' => 'required|numeric|min:0',
            'rows.*.kg_base'        => 'required|numeric|min:0',
            'rows.*.kg_optimistic'  => 'required|numeric|min:0',
        ]);

        foreach ($request->rows as $row) {
            $projectEvaluation->rows()->create([
                'variety_id'     => $row['variety_id'],
                'week'           => $row['week'],
                'hectares'       => $row['hectares'],
                'kg_pessimistic' => $row['kg_pessimistic'],
                'kg_base'        => $row['kg_base'],
                'kg_optimistic'  => $row['kg_optimistic'],
            ]);
        }

        return back();
    }
}

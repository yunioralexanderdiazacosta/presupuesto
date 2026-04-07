<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\KgYieldCost;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpsertKgYieldCostsController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation, Request $request)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);

        $data = $request->validate([
            'costs'            => 'required|array',
            'costs.*.kg_ha'    => 'required|numeric|min:0',
            'costs.*.cost_usd' => 'required|numeric|min:0',
        ]);

        // Reemplazar todos los registros de ESTA evaluación
        KgYieldCost::where('project_evaluation_id', $projectEvaluation->id)->delete();

        $records = collect($data['costs'])->map(fn($c) => [
            'team_id'                => $user->team_id,
            'project_evaluation_id'  => $projectEvaluation->id,
            'kg_ha'                  => $c['kg_ha'],
            'cost_usd'               => $c['cost_usd'],
        ])->toArray();

        KgYieldCost::insert($records);

        return back();
    }
}

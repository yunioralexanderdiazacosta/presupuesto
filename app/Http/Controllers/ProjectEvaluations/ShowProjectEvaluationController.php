<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\Fruit;
use App\Models\KgYieldCost;
use App\Models\ProjectEvaluation;
use App\Models\RnpPrice;
use App\Models\Variety;
use App\Models\VarietyCostParam;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowProjectEvaluationController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);

        $projectEvaluation->load(['rows.variety']);

        $varieties = Variety::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'fruit_id']);

        $fruits = Fruit::whereIn('id', $varieties->pluck('fruit_id')->unique()->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        // Precios RNP: [variety_id][week] => price_usd
        $rnpPrices = RnpPrice::where('project_evaluation_id', $projectEvaluation->id)
            ->get(['id', 'variety_id', 'week', 'price_usd']);

        // Parámetros de costo por variedad
        $varietyCostParams = VarietyCostParam::where('project_evaluation_id', $projectEvaluation->id)
            ->get(['id', 'variety_id', 'pct_embalaje', 'precio_proceso']);

        // Tabla de costo kg/rendimiento
        $kgYieldCosts = KgYieldCost::where('project_evaluation_id', $projectEvaluation->id)
            ->orderBy('kg_ha')
            ->get(['id', 'kg_ha', 'cost_usd']);

        return Inertia::render('ProjectEvaluations/Show', [
            'evaluation'        => $projectEvaluation,
            'rows'              => $projectEvaluation->rows,
            'varieties'         => $varieties,
            'fruits'            => $fruits,
            'rnpPrices'         => $rnpPrices,
            'varietyCostParams' => $varietyCostParams,
            'kgYieldCosts'      => $kgYieldCosts,
        ]);
    }
}

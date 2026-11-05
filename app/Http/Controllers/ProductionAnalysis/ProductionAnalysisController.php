<?php

namespace App\Http\Controllers\ProductionAnalysis;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CompanyReason;
use App\Models\CostCenterVariety;
use App\Models\Estimate;
use App\Models\EstimateStatus;
use App\Models\Fruit;
use App\Models\ProductionDispatch;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductionAnalysisController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        if (!$seasonId) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una temporada activa.');
        }

        $fruits = Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $estimateStatuses = EstimateStatus::whereIn('fruit_id', $fruits->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'name', 'fruit_id']);

        $branches = Branch::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $costCenterVarieties = CostCenterVariety::with(['costCenter:id,name,branch_id,company_reason_id', 'variety:id,name', 'fruit:id,name'])
            ->where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->get();

        // Estimación total = kilos_ha × superficie, separada por nombre de estimación
        $estimatesByCcv = Estimate::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->get(['cost_center_variety_id', 'estimate_status_id', 'kilos_ha'])
            ->groupBy('cost_center_variety_id');

        // Los kg vaciados a proceso y exportados solo existen en guías ya procesadas
        $dispatchesByCcv = ProductionDispatch::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->get(['cost_center_variety_id', 'status', 'kg_dispatched', 'kg_received', 'kg_exported'])
            ->groupBy('cost_center_variety_id');

        $rows = $costCenterVarieties->map(function ($ccv) use ($estimatesByCcv, $dispatchesByCcv) {
            $surface = (float) $ccv->surface;

            $estimates = [];
            foreach ($estimatesByCcv->get($ccv->id, collect()) as $estimate) {
                $estimates[$estimate->estimate_status_id] = round($surface * (float) $estimate->kilos_ha, 2);
            }

            $dispatches = $dispatchesByCcv->get($ccv->id, collect());
            $processed = $dispatches->where('status', 'processed');

            return [
                'ccv_id' => $ccv->id,
                'cost_center_name' => $ccv->costCenter->name ?? '-',
                'branch_id' => $ccv->costCenter->branch_id ?? null,
                'company_reason_id' => $ccv->costCenter->company_reason_id ?? null,
                'variety_id' => $ccv->variety_id,
                'variety_name' => $ccv->variety->name ?? 'Sin variedad',
                'fruit_id' => $ccv->fruit_id,
                'surface' => $surface,
                'estimates' => (object) $estimates,
                'guides' => $dispatches->count(),
                'guides_processed' => $processed->count(),
                'kg_dispatched' => round((float) $dispatches->sum('kg_dispatched'), 2),
                'kg_received' => round((float) $processed->sum('kg_received'), 2),
                'kg_exported' => round((float) $processed->sum('kg_exported'), 2),
            ];
        })->values();

        return Inertia::render('ProductionAnalysis/Index', [
            'fruits' => $fruits,
            'estimateStatuses' => $estimateStatuses,
            'branches' => $branches,
            'companyReasons' => $companyReasons,
            'rows' => $rows,
        ]);
    }
}

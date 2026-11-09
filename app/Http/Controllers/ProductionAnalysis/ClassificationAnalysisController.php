<?php

namespace App\Http\Controllers\ProductionAnalysis;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassificationGroup;
use App\Models\CompanyReason;
use App\Models\CostCenterVariety;
use App\Models\Fruit;
use App\Models\FruitClassificationType;
use App\Support\DispatchAllocation;
use App\Models\ProductionDispatchItem;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClassificationAnalysisController extends Controller
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

        $branches = Branch::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $costCenterVarieties = CostCenterVariety::with(['costCenter:id,name,branch_id,company_reason_id', 'variety:id,name'])
            ->where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->get();

        // Solo guías procesadas: sin proceso no se conocen los kg exportados ni su clasificación.
        // Cada guía se reparte entre sus cuarteles según los kg despachados de cada uno.
        $dispatchesByCcv = DispatchAllocation::forSeason($user->team_id, $seasonId)
            ->where('status', 'processed')
            ->groupBy('ccv_id');

        $share = DispatchAllocation::SHARE_SQL;
        $itemsByCcv = ProductionDispatchItem::query()
            ->join('production_dispatches', 'production_dispatches.id', '=', 'production_dispatch_items.production_dispatch_id')
            ->join('production_dispatch_cost_centers as pdcc', 'pdcc.production_dispatch_id', '=', 'production_dispatches.id')
            ->where('production_dispatches.team_id', $user->team_id)
            ->where('production_dispatches.season_id', $seasonId)
            ->where('production_dispatches.status', 'processed')
            ->groupBy(
                'pdcc.cost_center_variety_id',
                'production_dispatch_items.classification_type',
                'production_dispatch_items.classification_value'
            )
            ->selectRaw(
                "pdcc.cost_center_variety_id as ccv_id,
                 production_dispatch_items.classification_type as type,
                 production_dispatch_items.classification_value as value,
                 SUM(production_dispatch_items.kg * {$share}) as kg,
                 COALESCE(SUM(production_dispatch_items.boxes * {$share}), 0) as boxes"
            )
            ->get()
            ->groupBy('ccv_id');

        $rows = $costCenterVarieties->map(function ($ccv) use ($dispatchesByCcv, $itemsByCcv) {
            $dispatches = $dispatchesByCcv->get($ccv->id, collect());

            $classifications = $itemsByCcv->get($ccv->id, collect())
                ->groupBy('type')
                ->map(fn($list) => $list->map(fn($item) => [
                    'value' => $item->value,
                    'kg' => round((float) $item->kg, 2),
                    'boxes' => (int) $item->boxes,
                ])->values());

            return [
                'ccv_id' => $ccv->id,
                'cost_center_name' => $ccv->costCenter->name ?? '-',
                'branch_id' => $ccv->costCenter->branch_id ?? null,
                'company_reason_id' => $ccv->costCenter->company_reason_id ?? null,
                'variety_id' => $ccv->variety_id,
                'variety_name' => $ccv->variety->name ?? 'Sin variedad',
                'fruit_id' => $ccv->fruit_id,
                'surface' => (float) $ccv->surface,
                'guides_processed' => $dispatches->count(),
                'kg_exported' => round((float) $dispatches->sum('kg_exported'), 2),
                'classifications' => $classifications,
            ];
        })->values();

        $catalog = FruitClassificationType::where('team_id', $user->team_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['fruit_id', 'type', 'value']);

        $groups = ClassificationGroup::where('team_id', $user->team_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'fruit_id', 'classification_type', 'name', 'values']);

        return Inertia::render('ProductionAnalysis/Classifications', [
            'fruits' => $fruits,
            'branches' => $branches,
            'companyReasons' => $companyReasons,
            'rows' => $rows,
            'catalog' => $catalog,
            'groups' => $groups,
        ]);
    }
}

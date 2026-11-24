<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Estimates\StoreEstimateStatusRequest;
use App\Models\Estimate;
use App\Models\CostCenterVariety;
use App\Models\Fruit;
use App\Models\EstimateStatus;
use App\Traits\CheckSeasonLocked;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EstimatesController extends Controller
{
    use CheckSeasonLocked;

    /**
     * Crea un nombre de estimación (EstimateStatus). Si viene copy_from_id, copia además los kilos/ha y
     * observaciones de esa estimación para la temporada y equipo activos, así no hay que digitarlos de nuevo.
     */
    public function storeEstimateStatus(StoreEstimateStatusRequest $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $copyFromId = $request->input('copy_from_id');

        if ($copyFromId) {
            $this->abortIfSeasonLocked();
        }

        $copied = 0;

        $status = DB::transaction(function () use ($request, $user, $seasonId, $copyFromId, &$copied) {
            $status = EstimateStatus::create([
                'name' => trim($request->input('name')),
                'fruit_id' => $request->input('fruit_id'),
            ]);

            if ($copyFromId) {
                $sources = Estimate::where('estimate_status_id', $copyFromId)
                    ->where('season_id', $seasonId)
                    ->where('team_id', $user->team_id)
                    ->orderBy('id')
                    ->get()
                    ->unique('cost_center_variety_id');

                foreach ($sources as $source) {
                    Estimate::create([
                        'estimate_status_id' => $status->id,
                        'cost_center_variety_id' => $source->cost_center_variety_id,
                        'kilos_ha' => $source->kilos_ha,
                        'observations' => $source->observations ?? '',
                        'season_id' => $seasonId,
                        'team_id' => $user->team_id,
                    ]);
                    $copied++;
                }
            }

            return $status;
        });

        return response()->json(array_merge($status->toArray(), ['copied' => $copied]));
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Cargar variedades por cuartel con relaciones
        $costCenterVarieties = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->with(['costCenter', 'variety', 'fruit', 'rootstock', 'developmentState'])
            ->get();

        // Estimaciones con relaciones actualizadas
        $estimates = Estimate::where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->with(['costCenterVariety.costCenter', 'costCenterVariety.variety', 'costCenterVariety.fruit', 'estimateStatus'])
            ->get();

        $fruits = Fruit::where('team_id', $user->team_id)->get();
        $estimate_statuses = EstimateStatus::whereIn('fruit_id', $fruits->pluck('id'))->get();

        // Sucursales para el filtro del frontend
        $branches = \App\Models\Branch::where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name])
            ->values();

        return Inertia::render('Estimates', [
            'costCenterVarieties' => $costCenterVarieties,
            'estimates' => $estimates,
            'estimate_statuses' => $estimate_statuses,
            'fruits' => $fruits,
            'branches' => $branches,
            'season_id' => $season_id,
        ]);
    }
}



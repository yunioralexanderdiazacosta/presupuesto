<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\CostCenterVariety;
use App\Models\Fruit;
use App\Models\EstimateStatus;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EstimatesController extends Controller
{
    /**
     * Guarda un nuevo estado de estimación (EstimateStatus) desde el frontend.
     */
    public function storeEstimateStatus(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'fruit_id' => 'required|exists:fruits,id',
        ]);

        $fruit = Fruit::where('id', $request->fruit_id)
            ->where('team_id', $user->team_id)
            ->first();

        if (!$fruit) {
            return response()->json(['error' => 'Fruta no válida para este equipo.'], 403);
        }

        $status = EstimateStatus::create([
            'name' => $request->name,
            'fruit_id' => $request->fruit_id,
        ]);

        return response()->json($status);
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

        return Inertia::render('Estimates', [
            'costCenterVarieties' => $costCenterVarieties,
            'estimates' => $estimates,
            'estimate_statuses' => $estimate_statuses,
            'fruits' => $fruits,
            'season_id' => $season_id,
        ]);
    }
}



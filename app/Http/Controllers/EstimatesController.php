<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use Illuminate\Support\Facades\Auth;

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

        // Validar que la fruta pertenezca al equipo del usuario
        $fruit = \App\Models\Fruit::where('id', $request->fruit_id)
            ->where('team_id', $user->team_id)
            ->first();

        if (!$fruit) {
            return response()->json(['error' => 'Fruta no válida para este equipo.'], 403);
        }

        $status = \App\Models\EstimateStatus::create([
            'name' => $request->name,
            'fruit_id' => $request->fruit_id,
        ]);

        return response()->json($status);
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $costcenters = \App\Models\CostCenter::where('season_id', $season_id)
            ->with('variety')
            ->get();

        $fruitId = $request->input('fruit_id');
        $estimateStatusId = $request->input('estimate_status_id');
        $estimates = Estimate::query()
            // Filtrar por temporada y por el equipo del usuario
            ->where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->when($fruitId, function ($query) use ($fruitId) {
                $query->whereHas('costcenter', function ($q) use ($fruitId) {
                    $q->where('fruit_id', $fruitId);
                });
            })
            ->when($estimateStatusId, function ($query) use ($estimateStatusId) {
                $query->where('estimate_status_id', $estimateStatusId);
            })
            // Cargar relaciones para evitar consultas adicionales en la vista
            ->with(['costcenter.variety', 'estimateStatus'])
            ->get();

        // Frutas relacionadas al team (por costcenters del team)
        // Frutas relacionadas al team directamente por campo team_id en fruits
        $fruits = \App\Models\Fruit::where('team_id', $user->team_id)->get();

        // Estados relacionados a la fruta seleccionada
        $estimate_statuses = \App\Models\EstimateStatus::whereIn('fruit_id', $fruits->pluck('id'))->get();

        return \Inertia\Inertia::render('Estimates', [
            'costcenters' => $costcenters,
            'estimates' => $estimates,
            'estimate_statuses' => $estimate_statuses,
            'fruits' => $fruits,
            'season_id' => $season_id,
        ]);
    }
}



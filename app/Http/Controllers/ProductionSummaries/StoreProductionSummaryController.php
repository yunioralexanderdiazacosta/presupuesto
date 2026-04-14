<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionSummaries\StoreProductionSummaryRequest;
use App\Models\ProductionSummary;
use Illuminate\Support\Facades\Auth;

class StoreProductionSummaryController extends Controller
{
    public function __invoke(StoreProductionSummaryRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $records = $request->all();
        $errores = [];

        foreach ($records as $record) {
            ProductionSummary::updateOrCreate(
                [
                    'variety_id' => $record['variety_id'],
                    'season_id'  => $season_id,
                    'team_id'    => $user->team_id,
                ],
                [
                    'kg_harvested' => $record['kg_harvested'],
                    'kg_exported'  => $record['kg_exported'] ?? 0,
                    'net_kilo'     => $record['net_kilo'] ?? null,
                    'commercial_cost_per_kg' => $record['commercial_cost_per_kg'] ?? 0,
                    'observations' => $record['observations'] ?? '',
                ]
            );
        }

        $successMsg = 'Resumen de producción guardado correctamente.';
        if ($request->wantsJson()) {
            return response()->json(['success' => $successMsg]);
        }
        return redirect()->back()->with('success', $successMsg);
    }
}

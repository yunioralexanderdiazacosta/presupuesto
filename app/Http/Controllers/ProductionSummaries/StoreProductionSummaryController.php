<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionSummaries\StoreProductionSummaryRequest;
use App\Models\Production;
use App\Models\ProductionSummary;
use App\Models\Variety;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreProductionSummaryController extends Controller
{
    public function __invoke(StoreProductionSummaryRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $records = $request->all();

        // Derivar fruit_id desde la primera variedad del batch
        $firstVariety = Variety::find($records[0]['variety_id'] ?? null);
        $fruitId = $firstVariety?->fruit_id;

        if (!$fruitId) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No se pudo determinar la especie de las variedades.'], 422);
            }
            return redirect()->back()->with('error', 'No se pudo determinar la especie.');
        }

        // Obtener o crear la cabecera de producción
        $production = Production::firstOrCreate(
            ['season_id' => $season_id, 'team_id' => $user->team_id, 'fruit_id' => $fruitId],
            ['discount' => 0, 'advance' => 0]
        );

        foreach ($records as $record) {
            ProductionSummary::updateOrCreate(
                [
                    'production_id' => $production->id,
                    'variety_id'    => $record['variety_id'],
                ],
                [
                    'kg_harvested'           => $record['kg_harvested'],
                    'kg_exported'            => $record['kg_exported'] ?? 0,
                    'net_kilo'               => $record['net_kilo'] ?? null,
                    'commercial_cost_per_kg' => $record['commercial_cost_per_kg'] ?? 0,
                    'observations'           => $record['observations'] ?? '',
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

<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionSummaries\UpdateProductionSummaryRequest;
use App\Models\ProductionSummary;

use App\Traits\CheckSeasonLocked;

class UpdateProductionSummaryController extends Controller
{
    public function __invoke($id, UpdateProductionSummaryRequest $request)
    {
        $summary = ProductionSummary::find($id);

        if (!$summary) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Resumen no encontrado.'], 404);
            }
            return redirect()->back()->with('error', 'Resumen no encontrado.');
        }

        $summary->kg_harvested  = $request->kg_harvested;
        $summary->kg_exported   = $request->kg_exported ?? 0;
        $summary->net_kilo      = $request->net_kilo ?? null;
        $summary->commercial_cost_per_kg = $request->commercial_cost_per_kg ?? 0;
        $summary->observations  = $request->observations ?? '';
        $summary->save();

        $message = 'Resumen de producción actualizado correctamente.';
        if ($request->wantsJson()) {
            return response()->json(['success' => $message]);
        }
        return redirect()->back()->with('success', $message);
    }
}

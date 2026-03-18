<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Models\ProductionSummary;

class DeleteProductionSummaryController extends Controller
{
    public function __invoke($id)
    {
        $summary = ProductionSummary::find($id);

        if ($summary) {
            $summary->delete();
            $message = 'Resumen de producción eliminado correctamente.';
            if (request()->wantsJson()) {
                return response()->json(['success' => $message]);
            }
            return redirect()->back()->with('success', $message);
        }

        $errorMsg = 'Resumen no encontrado.';
        if (request()->wantsJson()) {
            return response()->json(['error' => $errorMsg], 404);
        }
        return redirect()->back()->with('error', $errorMsg);
    }
}

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
            $existe = ProductionSummary::where('variety_id', $record['variety_id'])
                ->where('season_id', $season_id)
                ->where('team_id', $user->team_id)
                ->exists();

            if ($existe) {
                $errores[] = "Ya existe un resumen para esa variedad en esta temporada.";
                continue;
            }

            ProductionSummary::create([
                'variety_id'   => $record['variety_id'],
                'kg_harvested' => $record['kg_harvested'],
                'kg_exported'  => $record['kg_exported'] ?? 0,
                'net_kilo'     => $record['net_kilo'] ?? null,
                'observations' => $record['observations'] ?? '',
                'season_id'    => $season_id,
                'team_id'      => $user->team_id,
            ]);
        }

        if (count($errores)) {
            $message = implode(' | ', $errores);
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $successMsg = 'Resumen de producción guardado correctamente.';
        if ($request->wantsJson()) {
            return response()->json(['success' => $successMsg]);
        }
        return redirect()->back()->with('success', $successMsg);
    }
}

<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\UpdateEstimateRequest;
use App\Models\Estimate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;    

class UpdateEstimateController extends Controller
{


      public function __construct()
    {
        Log::info('UpdateEstimateController: constructor ejecutado');
    }


public function __invoke($id, \Illuminate\Http\Request $request)
{
    Log::info('ID recibido en update:', ['id' => $id]);
    $estimate = \App\Models\Estimate::find($id);
    Log::info('Estimate encontrado:', $estimate ? $estimate->toArray() : ['not found']);

    if (!$estimate) {
        return response()->json(['error' => 'Estimate not found'], 404);
    }

    try {
        $estimate->estimate_status_id = $request->estimate_status_id;
        $estimate->kilos_ha = $request->kilos_ha;
        $estimate->cost_center_id = $request->cost_center_id;
        $estimate->observations = $request->observations;
        $estimate->season_id = $request->season_id;
        $estimate->team_id = auth()->user()->team_id;
        $estimate->save();
        return redirect()->back()->with('success', 'Estimación actualizada correctamente.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }
}
}

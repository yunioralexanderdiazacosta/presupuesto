<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\UpdateEstimateRequest;
use App\Models\Estimate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Traits\CheckSeasonLocked;    

class UpdateEstimateController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke($id, \Illuminate\Http\Request $request)
    {
        $this->abortIfSeasonLocked();
        $estimate = Estimate::find($id);

        if (!$estimate) {
            return response()->json(['error' => 'Estimación no encontrada.'], 404);
        }

        $estimate->estimate_status_id = $request->estimate_status_id;
        $estimate->kilos_ha = $request->kilos_ha;
        $estimate->cost_center_variety_id = $request->cost_center_variety_id;
        $estimate->observations = $request->observations;
        $estimate->save();

        $message = 'Estimación actualizada correctamente.';
        if ($request->wantsJson()) {
            return response()->json(['success' => $message]);
        }
        return redirect()->back()->with('success', $message);
    }
}

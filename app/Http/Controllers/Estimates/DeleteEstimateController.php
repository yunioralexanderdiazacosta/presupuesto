<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



class DeleteEstimateController extends Controller
{
    public function __invoke($id)
    {
        $estimate = Estimate::find($id);
        Log::info('Intentando eliminar estimate', ['id' => $id, 'found' => $estimate ? true : false]);

        if ($estimate) {
            $estimate->delete();
            Log::info('Estimate eliminado', ['id' => $id]);
            $message = 'Estimación eliminada correctamente.';
            if (request()->wantsJson()) {
                return response()->json(['success' => $message]);
            }
            return redirect()->back()->with('success', $message);
        } else {
            Log::warning('Estimate no encontrado para eliminar', ['id' => $id]);
            $errorMsg = 'Estimación no encontrada.';
            if (request()->wantsJson()) {
                return response()->json(['error' => $errorMsg], 404);
            }
            return redirect()->back()->with('error', $errorMsg);
        }
    }
}

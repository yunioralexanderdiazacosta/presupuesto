<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
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
            return redirect()->back()->with('success', 'Estimación eliminada correctamente.');
        } else {
            Log::warning('Estimate no encontrado para eliminar', ['id' => $id]);
            return redirect()->back()->with('error', 'Estimación no encontrada.');
        }
    }
}

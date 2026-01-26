<?php

namespace App\Http\Controllers\IrrigationPumps;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormIrrigationPumpRequest;
use App\Models\IrrigationPump;
use App\Models\IrrigationSector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateIrrigationPumpController extends Controller
{
    public function __invoke(FormIrrigationPumpRequest $request, IrrigationPump $irrigationPump)
    {
        $user = Auth::user();

        // Verificar que pertenece al equipo
        if ($irrigationPump->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        DB::beginTransaction();
        try {
            // Actualizar la bomba
            $irrigationPump->update([
                'name' => $request->name,
                'code' => $request->code,
                'brand' => $request->brand,
                'model' => $request->model,
            ]);

            // Eliminar sectores existentes y crear los nuevos
            $irrigationPump->sectors()->delete();

            if ($request->has('sectors') && is_array($request->sectors)) {
                foreach ($request->sectors as $sectorData) {
                    IrrigationSector::create([
                        'name' => $sectorData['name'],
                        'surface' => $sectorData['surface'],
                        'irrigation_pump_id' => $irrigationPump->id,
                        'observations' => $sectorData['observations'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('irrigation-pumps.index')->with('success', 'Bomba de riego actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la bomba: ' . $e->getMessage()]);
        }
    }
}

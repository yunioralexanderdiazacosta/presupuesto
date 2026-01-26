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

            // Obtener sectores actuales
            $currentSectorIds = $irrigationPump->sectors->pluck('id')->toArray();
            
            // Obtener IDs de sectores que se mantendrán (si tienen 'id' en el request)
            $keepingSectorIds = collect($request->sectors ?? [])
                ->filter(fn($s) => isset($s['id']))
                ->pluck('id')
                ->toArray();
            
            // Sectores a eliminar
            $sectorsToDelete = array_diff($currentSectorIds, $keepingSectorIds);
            
            // Validar que sectores a eliminar NO estén en uso
            if (!empty($sectorsToDelete)) {
                $sectorsInUse = DB::table('fertilizer_order_irrigation_sector')
                    ->whereIn('irrigation_sector_id', $sectorsToDelete)
                    ->exists();
                
                if ($sectorsInUse) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => 'No se puede eliminar sectores que están siendo usados en órdenes de fertilizantes. Por favor, elimine primero las órdenes asociadas o conserve los sectores.'
                    ])->withInput();
                }
                
                // Si no están en uso, eliminar
                IrrigationSector::whereIn('id', $sectorsToDelete)->delete();
            }

            // Actualizar o crear sectores
            if ($request->has('sectors') && is_array($request->sectors)) {
                foreach ($request->sectors as $sectorData) {
                    if (isset($sectorData['id'])) {
                        // Actualizar sector existente
                        IrrigationSector::where('id', $sectorData['id'])->update([
                            'name' => $sectorData['name'],
                            'surface' => $sectorData['surface'],
                            'observations' => $sectorData['observations'] ?? null,
                        ]);
                    } else {
                        // Crear nuevo sector
                        IrrigationSector::create([
                            'name' => $sectorData['name'],
                            'surface' => $sectorData['surface'],
                            'irrigation_pump_id' => $irrigationPump->id,
                            'observations' => $sectorData['observations'] ?? null,
                        ]);
                    }
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

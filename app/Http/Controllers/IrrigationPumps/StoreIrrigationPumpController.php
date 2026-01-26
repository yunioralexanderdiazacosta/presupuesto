<?php

namespace App\Http\Controllers\IrrigationPumps;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormIrrigationPumpRequest;
use App\Models\IrrigationPump;
use App\Models\IrrigationSector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreIrrigationPumpController extends Controller
{
    public function __invoke(FormIrrigationPumpRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        DB::beginTransaction();
        try {
            // Crear la bomba
            $pump = IrrigationPump::create([
                'name' => $request->name,
                'code' => $request->code,
                'brand' => $request->brand,
                'model' => $request->model,
                'team_id' => $user->team_id,
                'season_id' => $season_id,
            ]);

            // Crear los sectores asociados
            if ($request->has('sectors') && is_array($request->sectors)) {
                foreach ($request->sectors as $sectorData) {
                    IrrigationSector::create([
                        'name' => $sectorData['name'],
                        'surface' => $sectorData['surface'],
                        'irrigation_pump_id' => $pump->id,
                        'observations' => $sectorData['observations'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('irrigation-pumps.index')->with('success', 'Bomba de riego creada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la bomba: ' . $e->getMessage()]);
        }
    }
}

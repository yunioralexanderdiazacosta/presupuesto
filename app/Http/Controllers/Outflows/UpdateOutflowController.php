<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutflowRequest;
use App\Models\Outflow;

class UpdateOutflowController extends Controller
{
    public function __invoke(OutflowRequest $request, Outflow $outflow)
    {
        $data = $request->validated();

        // Extraer cost_center_ids antes de actualizar el outflow
        $costCenterIds = $data['cost_center_ids'] ?? [];
        unset($data['cost_center_ids']);

        // Actualizar el outflow
        $outflow->update($data);

        // Sincronizar los centros de costo
        if (!empty($costCenterIds)) {
            $outflow->costCenters()->delete(); // Eliminar los existentes
            foreach ($costCenterIds as $costCenterId) {
                $outflow->costCenters()->create([
                    'cost_center_id' => $costCenterId,
                    'observations' => null, // O puedes agregar observaciones si es necesario
                ]);
            }
        } else {
            // Si no hay cost centers, eliminar todos
            $outflow->costCenters()->delete();
        }

        return response()->json(['success' => true, 'message' => 'Salida actualizada correctamente.']);
    }
}

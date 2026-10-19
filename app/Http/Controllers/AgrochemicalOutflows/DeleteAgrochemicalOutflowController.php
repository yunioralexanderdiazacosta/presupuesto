<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use App\Models\Outflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\CheckSeasonLocked;

class DeleteAgrochemicalOutflowController
{
    public function __invoke(AgrochemicalOutflow $agrochemicalOutflow)
    {
        $user = Auth::user();
        
        // Validar que pertenezca al equipo del usuario
        if ($agrochemicalOutflow->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        DB::beginTransaction();
        
        try {
            $applicationOrderId = $agrochemicalOutflow->application_order_id;

            $this->adjustKardexForRemovedRow($agrochemicalOutflow);

            // Si el registro aún tiene el kardex vinculado (no quedan otros cuarteles en su línea),
            // el outflow y sus outflow_cost_centers se eliminan por cascade.
            $agrochemicalOutflow->delete();
            
            // Verificar si quedan aplicaciones para esta orden
            $remainingApplications = AgrochemicalOutflow::where('application_order_id', $applicationOrderId)->count();
            
            // Si no quedan, cambiar el estado de vuelta a 'pendiente'
            if ($remainingApplications === 0 && $applicationOrderId) {
                ApplicationOrder::where('id', $applicationOrderId)->update(['status' => 'pendiente']);
            }
            
            DB::commit();
            
            return redirect()->route('agrochemical-outflows.index')
                ->with('success', 'Aplicación de agroquímico eliminada correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la aplicación: ' . $e->getMessage()]);
        }
    }

    /**
     * Cada línea de factura genera un registro por cuartel y UN kardex con el total, vinculado al
     * último cuartel de la línea (los registros se agrupan por id hasta la fila que tiene el kardex).
     * Al quitar un cuartel, el kardex del grupo conserva el consumo de los cuarteles restantes.
     */
    private function adjustKardexForRemovedRow(AgrochemicalOutflow $removed): void
    {
        if (!$removed->application_order_id || !$removed->invoice_product_id) {
            return;
        }

        $rows = AgrochemicalOutflow::where('application_order_id', $removed->application_order_id)
            ->where('team_id', $removed->team_id)
            ->where('invoice_product_id', $removed->invoice_product_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'quantity', 'cost_center_id']);

        $kardexByRow = Outflow::whereIn('agrochemical_outflow_id', $rows->pluck('id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('agrochemical_outflow_id');

        // Ubicar el grupo (segmento de cuarteles + kardex) al que pertenece el registro eliminado
        $segment = collect();
        $kardex = null;
        foreach ($rows as $row) {
            $segment->push($row);
            if ($candidate = $kardexByRow->get($row->id)) {
                if ($segment->contains('id', $removed->id)) {
                    $kardex = $candidate;
                    break;
                }
                $segment = collect();
            }
        }

        if (!$kardex) {
            return;
        }

        $remaining = $segment->reject(fn ($row) => $row->id === $removed->id)->values();

        // Sin cuarteles restantes: el kardex se elimina por cascade junto con el registro
        if ($remaining->isEmpty()) {
            return;
        }

        $kardex->update([
            'agrochemical_outflow_id' => $remaining->last()->id,
            'quantity' => round($remaining->sum(fn ($row) => (float) $row->quantity), 4),
        ]);

        if ($removed->cost_center_id && !$remaining->contains('cost_center_id', $removed->cost_center_id)) {
            $kardex->costCenters()->where('cost_center_id', $removed->cost_center_id)->delete();
        }
    }
}

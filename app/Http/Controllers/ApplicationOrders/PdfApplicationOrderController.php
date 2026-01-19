<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfApplicationOrderController
{
    public function __invoke(ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();
        
        // Validar que la orden pertenezca al equipo del usuario
        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        // Cargar todas las relaciones necesarias
        $applicationOrder->load([
            'orderProducts.product.unit',
            'orderCostCenters.costCenter',
            'team',
            'season'
        ]);
        
        // Calcular total de hectáreas
        $totalHectareas = $applicationOrder->orderCostCenters->sum(function($occ) {
            return $occ->costCenter->surface ?? 0;
        });
        
        // Generar PDF
        $pdf = Pdf::loadView('pdfs.application-order', [
            'order' => $applicationOrder,
            'totalHectareas' => $totalHectareas,
        ]);
        
        // Configurar orientación y tamaño
        $pdf->setPaper('letter', 'portrait');
        
        // Nombre del archivo
        $filename = 'orden-aplicacion-' . $applicationOrder->id . '-' . now()->format('Y-m-d') . '.pdf';
        
        // Descargar o mostrar en navegador
        return $pdf->stream($filename);
    }
}

<?php

namespace App\Http\Controllers\FertilizerOrders;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfFertilizerOrderController extends Controller
{
    public function __invoke(FertilizerOrder $fertilizerOrder)
    {
        $user = Auth::user();
        
        // Verificar que la orden pertenezca al equipo del usuario
        if ($fertilizerOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        // Cargar relaciones necesarias
        $fertilizerOrder->load([
            'orderProducts.product.unit',
            'orderProducts.unit',
            'orderIrrigationSectors.irrigationSector',
            'orderCostCenters.costCenter',
            'irrigationPump',
            'team',
            'season'
        ]);

        $pdf = Pdf::loadView('pdfs.fertilizer-order', [
            'order' => $fertilizerOrder,
            'team' => $user->team,
        ]);

        return $pdf->stream('orden-fertilizante-' . $fertilizerOrder->id . '.pdf');
    }
}

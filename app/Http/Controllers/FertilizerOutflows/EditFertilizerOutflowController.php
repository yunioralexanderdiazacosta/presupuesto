<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use App\Traits\HasInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditFertilizerOutflowController extends Controller
{
    use HasInventory;
    public function __invoke(FertilizerOutflow $fertilizerOutflow)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        // Verificar que pertenezca al team del usuario
        if ($fertilizerOutflow->team_id !== $teamId) {
            abort(403, 'No autorizado');
        }

        // Cargar relaciones necesarias
        $fertilizerOutflow->load([
            'fertilizerOrder.orderCostCenters.costCenter',
            'fertilizerOrder.orderIrrigationSectors.irrigationSector',
            'fertilizerOrder.irrigationPump',
            'product.unit',
            'invoiceProduct.invoice',
            'costCenter'
        ]);

        // Calcular stock disponible del producto usando el trait
        $productId = $fertilizerOutflow->product_id;
        $currentInvoiceProductId = $fertilizerOutflow->invoice_product_id;
        
        // Obtener el outflow relacionado para excluirlo del cálculo
        $currentOutflow = Outflow::where('fertilizer_outflow_id', $fertilizerOutflow->id)->first();
        
        // Usar el método del trait para calcular stocks (excluyendo el outflow actual)
        $stocksByProduct = $this->getAvailableStocksByInvoiceProduct(
            $teamId, 
            $seasonId, 
            $currentOutflow ? $currentOutflow->id : null
        );

        // Filtrar solo el producto actual y formatear para el frontend
        $availableStocks = collect($stocksByProduct[$productId] ?? [])
            ->map(function($stock) use ($currentInvoiceProductId) {
                return [
                    'invoice_product_id' => $stock['invoice_product_id'],
                    'invoice_number' => $stock['number_document'],
                    'supplier_name' => $stock['supplier'],
                    'cantidad_original' => $stock['cantidad_original'],
                    'stock_disponible' => $stock['stock_disponible'],
                    'unit_name' => $stock['unit'],
                    'is_current' => $stock['invoice_product_id'] == $currentInvoiceProductId,
                ];
            })
            ->values();
        
        // Si la factura actual no está en la lista (porque tiene stock 0), agregarla
        if ($currentInvoiceProductId && !$availableStocks->contains('invoice_product_id', $currentInvoiceProductId)) {
            $currentInvoiceProduct = InvoiceProduct::with(['invoice.supplier', 'product.unit'])
                ->find($currentInvoiceProductId);
            
            if ($currentInvoiceProduct) {
                $availableStocks->prepend([
                    'invoice_product_id' => $currentInvoiceProduct->id,
                    'invoice_number' => $currentInvoiceProduct->invoice->number_document ?? 'N/A',
                    'supplier_name' => $currentInvoiceProduct->invoice->supplier->name ?? 'N/A',
                    'cantidad_original' => $currentInvoiceProduct->amount,
                    'stock_disponible' => 0,
                    'unit_name' => $currentInvoiceProduct->product->unit->name ?? 'unidad',
                    'is_current' => true,
                ]);
            }
        }

        return response()->json([
            'outflow' => $fertilizerOutflow,
            'availableStocks' => $availableStocks
        ]);
    }
}

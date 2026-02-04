<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use App\Models\Product;
use App\Models\ApplicationOrder;
use App\Http\Requests\AgrochemicalOutflows\StoreAgrochemicalOutflowRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreAgrochemicalOutflowController
{
    public function __invoke(StoreAgrochemicalOutflowRequest $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');
        
        // ========================================
        // VALIDAR STOCK DISPONIBLE PARA CADA LÍNEA
        // ========================================
        
        foreach ($request->products as $productData) {
            // Cada producto ahora tiene múltiples líneas
            foreach ($productData['lines'] as $line) {
                $invoiceProductId = $line['invoice_product_id'] ?? null;
                $quantity = $line['quantity'];
                
                if (!$invoiceProductId) {
                    continue;
                }
                
                $stockDisponible = 0;
                
                $ip = InvoiceProduct::findOrFail($invoiceProductId);
                $cantidadOriginal = $ip->quantity ?? $ip->amount ?? 0;
                $consumido = AgrochemicalOutflow::where('invoice_product_id', $invoiceProductId)->sum('quantity');
                
                // Calcular devoluciones
                $devuelto = CreditDebitNoteItem::whereHas('creditDebitNote', function($q) {
                    $q->where('type', 'credito');
                })
                ->where('invoice_product_id', $invoiceProductId)
                ->sum('quantity');
                
                $stockDisponible = $cantidadOriginal - $consumido - $devuelto;
                
                if ($quantity > $stockDisponible) {
                    $productName = Product::find($productData['product_id'])->name ?? 'Producto';
                    return back()->withErrors([
                        'stock' => "Stock insuficiente para {$productName}. Disponible: {$stockDisponible}"
                    ])->withInput();
                }
            }
        }
        
        // ========================================
        // CREAR AGROCHEMICAL OUTFLOWS Y OUTFLOWS
        // ========================================
        
        DB::beginTransaction();
        
        try {
            $applicationOrderId = $request->application_order_id;
            $maquinadas = $request->maquinadas;
            $date = $request->date;
            $observations = $request->observations;
            
            // Obtener cost centers de la orden de aplicación
            $applicationOrder = ApplicationOrder::with('orderCostCenters')->findOrFail($applicationOrderId);
            $orderCostCenters = $applicationOrder->orderCostCenters;
            
            foreach ($request->products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $costCenterId = $productData['cost_center_id'];
                
                // Crear un registro por cada línea de factura
                foreach ($productData['lines'] as $line) {
                    $quantity = $line['quantity'];
                    
                    // 1. Crear AgrochemicalOutflow
                    $agrochemicalOutflow = AgrochemicalOutflow::create([
                        'application_order_id' => $applicationOrderId,
                        'maquinadas' => $maquinadas,
                        'date' => $date,
                        'product_id' => $productData['product_id'],
                        'invoice_product_id' => $line['invoice_product_id'],
                        'quantity' => $quantity,
                        'cost_center_id' => $costCenterId,
                        'observations' => $observations,
                        'team_id' => $teamId,
                        'season_id' => $seasonId,
                    ]);
                    
                    // 2. Crear registro en Outflow para el kardex
                    $outflow = Outflow::create([
                        'agrochemical_outflow_id' => $agrochemicalOutflow->id,
                        'team_id' => $teamId,
                        'season_id' => $seasonId,
                        'user_id' => $user->id,
                        'invoice_product_id' => $line['invoice_product_id'],
                        'quantity' => $quantity,
                        'date' => $date,
                        'level3_id' => $product->level3_id,
                        'operation_id' => 1, // Gasto
                        'notes' => 'Aplicación agroquímico - Orden #' . $applicationOrderId . ' - ' . ($observations ?? 'Sin observaciones'),
                    ]);
                    
                    // 3. Crear MÚLTIPLES outflow_cost_centers (uno por cada cost center de la orden)
                    foreach ($orderCostCenters as $occ) {
                        $outflow->costCenters()->create([
                            'cost_center_id' => $occ->cost_center_id,
                            'observations' => $observations,
                        ]);
                    }
                }
            }
            
            // 4. Actualizar estado de la orden
            if ($applicationOrderId) {
                ApplicationOrder::find($applicationOrderId)->update(['status' => 'completada']);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Error al guardar: ' . $e->getMessage()
            ])->withInput();
        }
        
        return redirect()->route('agrochemical-outflows.index')
            ->with('success', 'Aplicación de agroquímicos registrada correctamente.');
    }
}

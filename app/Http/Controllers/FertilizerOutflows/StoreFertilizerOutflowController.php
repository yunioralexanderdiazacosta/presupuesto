<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Models\FertilizerOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use App\Models\Product;
use App\Models\FertilizerOrder;
use App\Http\Requests\FertilizerOutflows\StoreFertilizerOutflowRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreFertilizerOutflowController
{
    public function __invoke(StoreFertilizerOutflowRequest $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');
        
        // ========================================
        // VALIDAR STOCK DISPONIBLE PARA CADA LÍNEA
        // ========================================
        
        foreach ($request->products as $productData) {
            // Cada producto tiene múltiples líneas
            foreach ($productData['lines'] as $line) {
                $invoiceProductId = $line['invoice_product_id'] ?? null;
                $quantity = $line['quantity'];
                
                if (!$invoiceProductId) {
                    continue;
                }
                
                $stockDisponible = 0;
                
                $ip = InvoiceProduct::findOrFail($invoiceProductId);
                $cantidadOriginal = $ip->quantity ?? $ip->amount ?? 0;
                
                // Calcular consumos desde outflows (kardex maestro)
                $consumido = DB::table('outflows')
                    ->where('invoice_product_id', $invoiceProductId)
                    ->sum('quantity');
                
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
        // CREAR FERTILIZER OUTFLOWS Y OUTFLOWS
        // ========================================
        
        DB::beginTransaction();
        
        try {
            $fertilizerOrderId = $request->fertilizer_order_id;
            $date = $request->date;
            $observations = $request->observations;
            
            // Obtener cost centers de la orden
            $fertilizerOrder = FertilizerOrder::with('orderCostCenters')->findOrFail($fertilizerOrderId);
            $orderCostCenters = $fertilizerOrder->orderCostCenters;
            
            foreach ($request->products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                
                // Crear un registro por cada línea de factura
                foreach ($productData['lines'] as $line) {
                    $quantity = $line['quantity'];
                    
                    if ($quantity <= 0) {
                        continue;
                    }
                    
                    $invoiceProductId = $line['invoice_product_id'] ?? null;
                    
                    // Crear FertilizerOutflow
                    $fertilizerOutflow = FertilizerOutflow::create([
                        'fertilizer_order_id' => $fertilizerOrderId,
                        'date' => $date,
                        'product_id' => $product->id,
                        'invoice_product_id' => $invoiceProductId,
                        'quantity' => $quantity,
                        'unit_id' => $product->unit_id,
                        'cost_center_id' => $orderCostCenters->first()->cost_center_id ?? null, // Para compatibilidad
                        'observations' => $observations,
                        'team_id' => $teamId,
                        'season_id' => $seasonId,
                    ]);
                    
                    // Crear Outflow (kardex maestro)
                    if ($invoiceProductId) {
                        $outflow = Outflow::create([
                            'invoice_product_id' => $invoiceProductId,
                            'user_id' => $user->id,
                            'quantity' => $quantity,
                            'date' => $date,
                            'notes' => "Aplicación fertilizante - Orden #{$fertilizerOrderId}",
                            'team_id' => $teamId,
                            'season_id' => $seasonId,
                            'level3_id' => $product->level3_id,
                            'fertilizer_outflow_id' => $fertilizerOutflow->id,
                        ]);
                        
                        // Crear MÚLTIPLES outflow_cost_centers (uno por cada cost center de la orden)
                        foreach ($orderCostCenters as $occ) {
                            $outflow->costCenters()->create([
                                'cost_center_id' => $occ->cost_center_id,
                                'observations' => $observations,
                            ]);
                        }
                    }
                }
            }
            
            // Actualizar estado de la orden
            FertilizerOrder::where('id', $fertilizerOrderId)->update([
                'status' => 'completada'
            ]);
            
            DB::commit();
            
            return redirect()->route('fertilizer-outflows.index')->with('success', 'Aplicación de fertilizante registrada correctamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar la aplicación: ' . $e->getMessage()])->withInput();
        }
    }
}

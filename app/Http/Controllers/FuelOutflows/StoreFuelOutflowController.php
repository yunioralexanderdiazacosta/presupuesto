<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use App\Models\Product;
use App\Http\Requests\FuelOutflows\StoreFuelOutflowRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreFuelOutflowController
{
    public function __invoke(StoreFuelOutflowRequest $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');
        
        // ========================================
        // 🔥 VALIDAR STOCK DISPONIBLE
        // ========================================
        
        $invoiceProductId = $request->input('invoice_product_id');
        $creditDebitNoteItemId = $request->input('credit_debit_note_item_id');
        $liters = $request->input('liters');
        
        $stockDisponible = 0;
        
        if ($invoiceProductId) {
            $ip = InvoiceProduct::findOrFail($invoiceProductId);
            $cantidadOriginal = $ip->quantity ?? $ip->amount ?? 0;
            $consumido = FuelOutflow::where('invoice_product_id', $invoiceProductId)->sum('liters');
            
            // Calcular devoluciones (notas de crédito)
            $devuelto = \App\Models\CreditDebitNoteItem::whereHas('creditDebitNote', function($q) {
                $q->where('type', 'credito');
            })
            ->where('invoice_product_id', $invoiceProductId)
            ->sum('quantity');
            
            $stockDisponible = $cantidadOriginal - $consumido - $devuelto;
            
        } elseif ($creditDebitNoteItemId) {
            $item = CreditDebitNoteItem::findOrFail($creditDebitNoteItemId);
            $cantidadOriginal = $item->quantity ?? 0;
            $consumido = FuelOutflow::where('credit_debit_note_item_id', $creditDebitNoteItemId)->sum('liters');
            $stockDisponible = $cantidadOriginal - $consumido;
        }
        
        // Validar que no exceda el stock
        if ($liters > $stockDisponible) {
            return back()->withErrors([
                'liters' => "No hay suficiente stock. Disponible: {$stockDisponible} litros"
            ])->withInput();
        }
        
        // ========================================
        // 🔥 CREAR FUEL OUTFLOW Y OUTFLOW EN TRANSACCIÓN
        // ========================================
        
        $validated = $request->validated();
        $validated['team_id'] = $teamId;
        $validated['season_id'] = $seasonId;
        
        $costCenters = $validated['cost_center_id'] ?? [];
        unset($validated['cost_center_id']);
        
        DB::beginTransaction();
        
        try {
            // 1. Crear FuelOutflow
            $fuelOutflow = FuelOutflow::create($validated);
            
            // 2. Obtener level3_id de combustible desde el producto
            $product = Product::findOrFail($validated['product_id']);
            $level3Id = $product->level3_id;
            
            // 3. Crear registro en Outflow para el kardex
            $outflow = Outflow::create([
                'fuel_outflow_id' => $fuelOutflow->id,
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'user_id' => $user->id,
                'invoice_product_id' => $validated['invoice_product_id'] ?? null,
                'credit_debit_note_item_id' => $validated['credit_debit_note_item_id'] ?? null,
                'machinery_id' => $validated['machinery_id'] ?? null,
                'quantity' => $validated['liters'],
                'date' => $validated['date'],
                'level3_id' => $level3Id,
                'notes' => 'Consumo de combustible - ' . ($validated['observations'] ?? 'Sin observaciones'),
            ]);
            
            // 4. Crear centros de costo en outflow_cost_center (asociados al Outflow)
            if (!empty($costCenters)) {
                foreach ($costCenters as $ccId) {
                    $outflow->costCenters()->create([
                        'cost_center_id' => $ccId,
                        'observations' => $validated['observations'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Error al guardar: ' . $e->getMessage()
            ])->withInput();
        }
        
        return redirect()->route('fuel-outflows.index')
            ->with('success', 'Consumo de combustible registrado correctamente.');
    }
}

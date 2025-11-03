<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use App\Http\Requests\FuelOutflows\StoreFuelOutflowRequest;
use Illuminate\Support\Facades\Auth;

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
        // 🔥 CREAR FUEL OUTFLOW
        // ========================================
        
        $validated = $request->validated();
        $validated['team_id'] = $teamId;
        $validated['season_id'] = $seasonId;
        
        $costCenters = $validated['cost_center_id'] ?? [];
        unset($validated['cost_center_id']);
        
        $fuelOutflow = FuelOutflow::create($validated);
        
        if (!empty($costCenters)) {
            // Eliminar registros existentes (por si acaso)
            $fuelOutflow->costCenters()->delete();
            // Crear los nuevos
            foreach ($costCenters as $ccId) {
                $fuelOutflow->costCenters()->create([
                    'cost_center_id' => $ccId,
                    'observations' => $validated['observations'] ?? null,
                ]);
            }
        }
        
        return redirect()->route('fuel-outflows.index')
            ->with('success', 'Consumo de combustible registrado correctamente.');
    }
}

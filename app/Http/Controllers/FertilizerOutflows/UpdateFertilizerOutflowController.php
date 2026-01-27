<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateFertilizerOutflowController extends Controller
{
    public function __invoke(Request $request, FertilizerOutflow $fertilizerOutflow)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        // Verificar que pertenezca al team del usuario
        if ($fertilizerOutflow->team_id !== $teamId) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'date' => 'required|date',
            'invoice_product_id' => 'required|exists:invoice_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'observations' => 'nullable|string',
        ]);

        // Validar stock disponible
        $invoiceProductId = $request->invoice_product_id;
        $quantity = $request->quantity;
        
        $ip = InvoiceProduct::findOrFail($invoiceProductId);
        $cantidadOriginal = $ip->quantity ?? $ip->amount ?? 0;
        
        // Calcular consumos excluyendo ESTE registro que estamos editando
        $consumido = DB::table('outflows')
            ->where('invoice_product_id', $invoiceProductId)
            ->where('id', '!=', DB::table('outflows')->where('fertilizer_outflow_id', $fertilizerOutflow->id)->value('id'))
            ->sum('quantity');
        
        // Calcular devoluciones
        $devuelto = CreditDebitNoteItem::whereHas('creditDebitNote', function($q) {
            $q->where('type', 'credito');
        })
        ->where('invoice_product_id', $invoiceProductId)
        ->sum('quantity');
        
        $stockDisponible = $cantidadOriginal - $consumido - $devuelto;
        
        if ($quantity > $stockDisponible) {
            return back()->withErrors([
                'quantity' => "Stock insuficiente. Disponible: {$stockDisponible}"
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            // Actualizar fertilizer outflow
            $fertilizerOutflow->update([
                'date' => $request->date,
                'invoice_product_id' => $invoiceProductId,
                'quantity' => $quantity,
                'observations' => $request->observations,
            ]);

            // Actualizar outflow relacionado
            $outflow = Outflow::where('fertilizer_outflow_id', $fertilizerOutflow->id)->first();
            if ($outflow) {
                $outflow->update([
                    'date' => $request->date,
                    'invoice_product_id' => $invoiceProductId,
                    'quantity' => $quantity,
                    'notes' => "Aplicación fertilizante - Orden #{$fertilizerOutflow->fertilizer_order_id}" . ($request->observations ? " - {$request->observations}" : ''),
                ]);
            }

            DB::commit();

            return back()->with('success', 'Aplicación actualizada correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }
}

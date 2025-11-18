<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice, FormInvoiceRequest $request)
    {
        // Verificar si la factura tiene salidas (outflows) asociadas
        // Buscar en invoice_product los registros de esta factura
        $invoiceProductIds = DB::table('invoice_product')
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        Log::info('UpdateInvoice - Invoice ID: ' . $invoice->id);
        Log::info('UpdateInvoice - Invoice Product IDs: ' . $invoiceProductIds->toJson());

        if ($invoiceProductIds->isNotEmpty()) {
            $outflows = \App\Models\Outflow::whereIn('invoice_product_id', $invoiceProductIds)->get();
            
            Log::info('UpdateInvoice - Has Outflows: ' . ($outflows->isNotEmpty() ? 'YES' : 'NO'));
            
            if ($outflows->isNotEmpty()) {
                Log::info('UpdateInvoice - BLOCKING EDIT - has outflows');
                
                $outflowIds = $outflows->pluck('id')->join(', #');
                $count = $outflows->count();
                $message = $count === 1 
                    ? "No se puede editar esta factura porque ya tiene una salida de producto asociada (Salida #{$outflowIds})."
                    : "No se puede editar esta factura porque ya tiene {$count} salidas de productos asociadas (Salidas #{$outflowIds}).";
                
                return redirect()->back()->with('error', $message);
            }
        }

        Log::info('UpdateInvoice - ALLOWING EDIT - no outflows');

        $invoice->payment_term      = $request->payment_term;
        $invoice->payment_type      = $request->payment_type;
        $invoice->petty_cash        = $request->petty_cash;
        $invoice->supplier_id       = $request->supplier_id;
        $invoice->company_reason_id = $request->company_reason_id;
        $invoice->type_document_id  = $request->type_document_id;
        $invoice->number_document   = $request->number_document;
        $invoice->month_id          = $request->month_id;
        $invoice->date              = $request->date;
        $invoice->due_date          = $request->due_date;
        $invoice->save();

        // Eliminar productos actuales
        $invoice->products()->detach();
        // Agregar cada línea de producto
        foreach ($this->products($request->products) as $productAttach) {
            $invoice->products()->attach($productAttach['product_id'], [
                'unit_price'   => $productAttach['unit_price'],
                'amount'       => $productAttach['amount'],
                'observations' => $productAttach['observations'],
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Factura actualizada correctamente');
    }

    public function products($products)
    {
        // Obtener team_id fresco del usuario actual
        $currentTeamId = \App\Models\User::find(auth()->id())->team_id;
        
        $data = [];
        foreach ($products as $item) {
            // Gestionar unidad: buscar o crear
            $unitId = $item['unit_id'] ?? null;
            if (!is_numeric($unitId) || !\App\Models\Unit::find($unitId)) {
                $u = \App\Models\Unit::firstOrCreate(['name' => $unitId]);
                $unitId = $u->id;
            }
            
            // Gestionar producto: buscar o crear
            $prodId = $item['product_id'];
            
            // Si el product_id no es numérico, es un nombre nuevo
            if (!is_numeric($prodId)) {
                // Buscar producto existente por nombre (case-insensitive)
                $existingProduct = \App\Models\Product::whereRaw('LOWER(name) = ?', [strtolower(trim($prodId))])
                    ->where('team_id', $currentTeamId)
                    ->first();
                
                if ($existingProduct) {
                    // Si existe, usar el producto existente
                    $prodId = $existingProduct->id;
                } else {
                    // Si no existe, crear nuevo producto
                    $newProduct = \App\Models\Product::create([
                        'name'    => trim($prodId),
                        'team_id' => $currentTeamId,
                        'unit_id' => $unitId,
                    ]);
                    $prodId = $newProduct->id;
                }
            } else {
                // Si es numérico, verificar que exista
                if (!\App\Models\Product::find($prodId)) {
                    // Si no existe, crear con el ID como nombre (fallback)
                    $newProduct = \App\Models\Product::create([
                        'name'    => $prodId,
                        'team_id' => $currentTeamId,
                        'unit_id' => $unitId,
                    ]);
                    $prodId = $newProduct->id;
                }
            }
            
            $data[] = [
                'product_id'   => $prodId,
                'unit_price'   => $item['unit_price'],
                'amount'       => $item['amount'],
                'observations' => $item['observations'],
            ];
        }
        return $data;
    }
}

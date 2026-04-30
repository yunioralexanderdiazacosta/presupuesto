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
        // Identificar qué invoice_products tienen salidas (outflows) asociadas
        $allInvoiceProductIds = DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        $protectedInvoiceProductIds = collect();
        $protectedProductIds = collect();

        if ($allInvoiceProductIds->isNotEmpty()) {
            $protectedInvoiceProductIds = \App\Models\Outflow::whereIn('invoice_product_id', $allInvoiceProductIds)
                ->pluck('invoice_product_id')
                ->unique();

            $protectedProductIds = DB::table('invoice_products')
                ->whereIn('id', $protectedInvoiceProductIds)
                ->pluck('product_id');
        }

        // Verificar que no se hayan eliminado productos protegidos del listado
        if ($protectedProductIds->isNotEmpty()) {
            $submittedProductIds = collect($request->products)
                ->pluck('product_id')
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int) $id);

            $missingProtected = $protectedProductIds->diff($submittedProductIds);

            if ($missingProtected->isNotEmpty()) {
                $productNames = \App\Models\Product::whereIn('id', $missingProtected)->pluck('name')->join(', ');
                $outflowIds = \App\Models\Outflow::whereIn('invoice_product_id',
                    DB::table('invoice_products')
                        ->where('invoice_id', $invoice->id)
                        ->whereIn('product_id', $missingProtected)
                        ->pluck('id')
                )->pluck('id')->join(', #');

                return redirect()->back()->with('error',
                    "No se puede eliminar el producto \"{$productNames}\" porque tiene salidas asociadas (Salidas #{$outflowIds})."
                );
            }
        }

        Log::info('UpdateInvoice - Invoice ID: ' . $invoice->id);

        // Actualizar campos de cabecera de la factura
        $invoice->payment_term      = $request->payment_term;
        $invoice->payment_type      = $request->payment_type;
        $invoice->supplier_id       = $request->supplier_id;
        $invoice->company_reason_id = $request->company_reason_id;
        $invoice->type_document_id  = $request->type_document_id;
        $invoice->number_document   = $request->number_document;
        $invoice->month_id          = $request->month_id;
        $invoice->purchase_order_id = $request->purchase_order_id;
        $invoice->date              = $request->date;
        $invoice->due_date          = $request->due_date;
        $invoice->save();

        // Eliminar solo los productos que NO tienen salidas asociadas
        DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->whereNotIn('id', $protectedInvoiceProductIds->toArray())
            ->delete();

        // Re-agregar solo los productos que no están protegidos
        $protectedProductIdsArray = $protectedProductIds->toArray();
        foreach ($this->products($request->products) as $productAttach) {
            if (!in_array((int) $productAttach['product_id'], $protectedProductIdsArray)) {
                $invoice->products()->attach($productAttach['product_id'], [
                    'unit_price'   => $productAttach['unit_price'],
                    'amount'       => $productAttach['amount'],
                    'observations' => $productAttach['observations'],
                    'is_exento'    => $productAttach['is_exento'] ?? false,
                    'branch_id'    => $productAttach['branch_id'] ?? null,
                ]);
            }
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
                'is_exento'    => $item['is_exento'] ?? false,
                'branch_id'    => $item['branch_id'] ?? null,
            ];
        }
        return $data;
    }
}

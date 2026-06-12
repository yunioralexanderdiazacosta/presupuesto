<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\CheckSeasonLocked;

class UpdateInvoiceController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Invoice $invoice, FormInvoiceRequest $request)
    {
        $this->abortIfSeasonLocked();
        // Identificar qué invoice_products tienen salidas (outflows) asociadas
        $allInvoiceProductIds = DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        $protectedInvoiceProductIds = collect();
        $protectedProductIds = collect();
        // Líneas referenciadas por notas de crédito/débito (no se deben borrar ni recrear)
        $creditNoteInvoiceProductIds = collect();

        if ($allInvoiceProductIds->isNotEmpty()) {
            $outflowProtectedIds = \App\Models\Outflow::whereIn('invoice_product_id', $allInvoiceProductIds)
                ->pluck('invoice_product_id')
                ->unique();

            // Proteger también las líneas vinculadas a notas de crédito/débito,
            // para no romper el vínculo (invoice_product_id) al editar la factura.
            $creditNoteInvoiceProductIds = DB::table('credit_debit_note_items')
                ->whereIn('invoice_product_id', $allInvoiceProductIds)
                ->pluck('invoice_product_id')
                ->unique();

            // Conjunto combinado de líneas protegidas (salidas + notas de crédito)
            $protectedInvoiceProductIds = $outflowProtectedIds
                ->merge($creditNoteInvoiceProductIds)
                ->unique()
                ->values();

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

                // Determinar el motivo de la protección para mostrar un mensaje claro
                $missingProtectedIpIds = DB::table('invoice_products')
                    ->where('invoice_id', $invoice->id)
                    ->whereIn('product_id', $missingProtected)
                    ->pluck('id');

                $hasOutflow = \App\Models\Outflow::whereIn('invoice_product_id', $missingProtectedIpIds)->exists();
                $hasCreditNote = DB::table('credit_debit_note_items')
                    ->whereIn('invoice_product_id', $missingProtectedIpIds)
                    ->exists();

                if ($hasOutflow) {
                    $outflowIds = \App\Models\Outflow::whereIn('invoice_product_id', $missingProtectedIpIds)
                        ->pluck('id')->join(', #');
                    return redirect()->back()->with('error',
                        "No se puede eliminar el producto \"{$productNames}\" porque tiene salidas asociadas (Salidas #{$outflowIds})."
                    );
                }

                if ($hasCreditNote) {
                    $noteNumbers = DB::table('credit_debit_note_items')
                        ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                        ->whereIn('credit_debit_note_items.invoice_product_id', $missingProtectedIpIds)
                        ->pluck('credit_debit_notes.number')->unique()->join(', ');
                    return redirect()->back()->with('error',
                        "No se puede eliminar el producto \"{$productNames}\" porque tiene notas de crédito/débito asociadas (Nota(s): {$noteNumbers})."
                    );
                }

                return redirect()->back()->with('error',
                    "No se puede eliminar el producto \"{$productNames}\" porque tiene movimientos asociados."
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

        // Eliminar solo las líneas que NO están protegidas (sin salidas ni notas de crédito asociadas)
        DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->whereNotIn('id', $protectedInvoiceProductIds->toArray())
            ->delete();

        // Re-agregar productos no protegidos y actualizar tank_id/branch_id en protegidos
        $protectedProductIdsArray = $protectedProductIds->toArray();
        foreach ($request->products as $rawItem) {
            $productId = (int) $rawItem['product_id'];
            if (in_array($productId, $protectedProductIdsArray)) {
                // Actualizar campos que no afectan la integridad de las salidas
                $invoiceProductId = $rawItem['invoice_product_id'] ?? null;
                if ($invoiceProductId) {
                    $unitPrice = isset($rawItem['unit_price']) ? (float) $rawItem['unit_price'] : null;
                    $amount    = isset($rawItem['amount'])     ? (float) $rawItem['amount']     : null;
                    DB::table('invoice_products')
                        ->where('id', $invoiceProductId)
                        ->where('invoice_id', $invoice->id)
                        ->update([
                            'unit_price'   => $unitPrice,
                            'amount'       => $amount,
                            'observations' => $rawItem['observations'] ?? null,
                            'is_exento'    => $rawItem['is_exento'] ?? false,
                            'tank_id'      => $rawItem['tank_id'] ?? null,
                            'branch_id'    => $rawItem['branch_id'] ?? null,
                        ]);
                }
            }
        }

        foreach ($this->products($request->products) as $productAttach) {
            // Una línea es protegida si tiene un invoice_product_id existente con salidas o notas de crédito.
            // Líneas nuevas (sin invoice_product_id) siempre se guardan.
            $ipId = $productAttach['invoice_product_id'] ?? null;
            $isProtectedLine = $ipId && $protectedInvoiceProductIds->contains((int) $ipId);

            if (!$isProtectedLine) {
                $invoice->products()->attach($productAttach['product_id'], [
                    'unit_price'   => $productAttach['unit_price'],
                    'amount'       => $productAttach['amount'],
                    'observations' => $productAttach['observations'],
                    'is_exento'    => $productAttach['is_exento'] ?? false,
                    'branch_id'    => $productAttach['branch_id'] ?? null,
                    'tank_id'      => $productAttach['tank_id'] ?? null,
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
                'product_id'        => $prodId,
                'invoice_product_id'=> isset($item['invoice_product_id']) && is_numeric($item['invoice_product_id']) ? (int) $item['invoice_product_id'] : null,
                'unit_price'        => $item['unit_price'],
                'amount'            => $item['amount'],
                'observations'      => $item['observations'],
                'is_exento'         => $item['is_exento'] ?? false,
                'branch_id'         => $item['branch_id'] ?? null,
                'tank_id'           => $item['tank_id'] ?? null,
            ];
        }
        return $data;
    }
}

<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormInvoiceRequest;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ExpenseReportItem;
use Illuminate\Support\Facades\DB;

class StoreInvoiceController extends Controller
{
    public function __invoke(FormInvoiceRequest $request)
    {
        // Obtener usuario actualizado directamente de la base de datos para evitar caché
        $user = \App\Models\User::find(Auth::id());

        $season_id = session('season_id');

        DB::transaction(function() use ($request, $user, $season_id) {
            $invoice = Invoice::create([
                'payment_term'      => $request->payment_term,
                'payment_type'      => $request->payment_type,
                'team_id'           => $user->team_id,
                'user_id'           => $user->id,
                'supplier_id'       => $request->supplier_id,
                'company_reason_id' => $request->company_reason_id,
                'type_document_id'  => $request->type_document_id,
                'number_document'   => $request->number_document,
                'date'              => $request->date,
                'due_date'          => $request->due_date,
                'season_id'         => $season_id,
                'month_id'          => $request->month_id,
                'purchase_order_id' => $request->purchase_order_id,
            ]);

            foreach ($this->products($request->products) as $productAttach) {
                $invoice->products()->attach($productAttach['product_id'], [
                    'unit_price'   => $productAttach['unit_price'],
                    'amount'       => $productAttach['amount'],
                    'observations' => $productAttach['observations'],
                ]);
            }

            // Si viene de una rendición de gastos, vincular el item y guardar la referencia
            if ($request->expense_item_id) {
                $item = ExpenseReportItem::whereNull('invoice_id')
                    ->whereHas('expenseReport', function ($q) use ($user) {
                        $q->where('team_id', $user->team_id);
                    })
                    ->find($request->expense_item_id);

                if ($item) {
                    $item->update(['invoice_id' => $invoice->id]);
                    $invoice->update(['expense_report_id' => $item->expense_report_id]);
                }
            }
        });
    }

    public function products($products)
    {
        // Obtener team_id fresco del usuario actual
        $currentTeamId = \App\Models\User::find(Auth::id())->team_id;
        
        $data = [];
        foreach ($products as $item) {
            // Gestionar unidad: buscar o crear
            $unitId = $item['unit_id'] ?? null;
            if (!is_numeric($unitId) || !Unit::find($unitId)) {
                $u = Unit::firstOrCreate(['name' => $unitId]);
                $unitId = $u->id;
            }
            
            // Gestionar producto: buscar o crear
            $prodId = $item['product_id'];
            
            // Si el product_id no es numérico, es un nombre nuevo
            if (!is_numeric($prodId)) {
                // Buscar producto existente por nombre (case-insensitive)
                $existingProduct = Product::whereRaw('LOWER(name) = ?', [strtolower(trim($prodId))])
                    ->where('team_id', $currentTeamId)
                    ->first();
                
                if ($existingProduct) {
                    // Si existe, usar el producto existente
                    $prodId = $existingProduct->id;
                } else {
                    // Si no existe, crear nuevo producto
                    $newProduct = Product::create([
                        'name'    => trim($prodId),
                        'team_id' => $currentTeamId,
                        'unit_id' => $unitId,
                    ]);
                    $prodId = $newProduct->id;
                }
            } else {
                // Si es numérico, verificar que exista
                if (!Product::find($prodId)) {
                    // Si no existe, crear con el ID como nombre (fallback)
                    $newProduct = Product::create([
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

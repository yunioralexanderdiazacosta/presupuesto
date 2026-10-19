<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unit;
use Inertia\Inertia;

class CreateCreditDebitNoteController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        // Cantidad que aún queda en bodega por línea (máximo que se puede devolver físicamente)
        $consumedByLine = DB::table('outflows')
            ->where('team_id', $user->team_id)
            ->whereNotNull('invoice_product_id')
            ->groupBy('invoice_product_id')
            ->selectRaw('invoice_product_id, SUM(quantity) as consumed_quantity')
            ->pluck('consumed_quantity', 'invoice_product_id');
        $returnedByLine = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->selectRaw('credit_debit_note_items.invoice_product_id, SUM(credit_debit_note_items.quantity) as returned_quantity')
            ->pluck('returned_quantity', 'invoice_product_id');

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
        });

        $invoices = Invoice::where('team_id', $user->team_id)->get()->transform(function($invoice) use ($consumedByLine, $returnedByLine) {
            // Obtener líneas de factura para asociar cred/deb
            $productosFactura = $invoice->invoiceProducts()->with(['product', 'branch:id,name'])->get()->map(function($line) use ($consumedByLine, $returnedByLine) {
                $consumed = (float) ($consumedByLine[$line->id] ?? 0);
                $returned = (float) ($returnedByLine[$line->id] ?? 0);
                return [
                    'label'             => $line->product->name,
                    'value'             => $line->id,            // id de invoice_product
                    'product_id'        => $line->product_id,
                    'unit_id'           => $line->unit_id ?? $line->product->unit_id,
                    'unit_price'        => $line->unit_price,
                    'branch_id'         => $line->branch_id,
                    'branch_name'       => $line->branch?->name,
                    'amount'            => $line->amount,
                    'consumed'          => round($consumed, 4),
                    'returned'          => round($returned, 4),
                    'available'         => round(max(0, (float) $line->amount - $consumed - $returned), 4),
                ];
            })->values()->all();
            return [
                'label'       => $invoice->number_document,
                'value'       => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'products'    => $productosFactura
            ];
        });

        $products = Product::where('team_id', $user->team_id)->get()->transform(function($product){
            return [
                'label'   => $product->name,
                'value'   => $product->id,
                'unit_id' => $product->unit_id,
            ];
        });

        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

        $season_id = session('season_id');
        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name]);

        return Inertia::render('CreditDebitNotes/Create', compact('suppliers', 'invoices', 'products', 'units', 'branches'));
    }
}

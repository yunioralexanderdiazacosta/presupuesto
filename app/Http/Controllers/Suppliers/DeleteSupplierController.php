<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;

class DeleteSupplierController extends Controller
{
    public function __invoke(Supplier $supplier)
    {
        // Documentos que dependen del proveedor. Si existe alguno,
        // impedir la eliminación para evitar el borrado en cascada
        // de facturas / órdenes de compra y la pérdida de información.
        $bloqueos = [];

        $invoices = DB::table('invoices')->where('supplier_id', $supplier->id)->count();
        if ($invoices > 0) {
            $bloqueos[] = $invoices . ' factura(s)';
        }

        $purchaseOrders = DB::table('purchase_orders')->where('supplier_id', $supplier->id)->count();
        if ($purchaseOrders > 0) {
            $bloqueos[] = $purchaseOrders . ' orden(es) de compra';
        }

        $creditDebitNotes = DB::table('credit_debit_notes')->where('supplier_id', $supplier->id)->count();
        if ($creditDebitNotes > 0) {
            $bloqueos[] = $creditDebitNotes . ' nota(s) de crédito/débito';
        }

        $expenseItems = DB::table('expense_report_items')->where('supplier_id', $supplier->id)->count();
        if ($expenseItems > 0) {
            $bloqueos[] = $expenseItems . ' ítem(s) de rendición de gastos';
        }

        if (!empty($bloqueos)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'supplier' => 'No se puede eliminar el proveedor "' . $supplier->name
                    . '" porque tiene ' . implode(', ', $bloqueos)
                    . ' asociada(s). Elimine o reasigne esos documentos antes de borrar el proveedor.',
            ]);
        }

        $supplier->delete();
    }
}

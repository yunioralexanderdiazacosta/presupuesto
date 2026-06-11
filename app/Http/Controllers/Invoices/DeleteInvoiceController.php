<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\CheckSeasonLocked;

class DeleteInvoiceController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Invoice $invoice, Request $request)
    {
        $this->abortIfSeasonLocked();

        $invoiceProductIds = DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        if ($invoiceProductIds->isNotEmpty()) {
            $usedProducts = Outflow::whereIn('invoice_product_id', $invoiceProductIds)
                ->join('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
                ->join('products', 'invoice_products.product_id', '=', 'products.id')
                ->pluck('products.name')
                ->unique()
                ->values();

            if ($usedProducts->isNotEmpty()) {
                $outflowCount = Outflow::whereIn('invoice_product_id', $invoiceProductIds)->count();

                // Si viene force=1, eliminar primero las salidas y luego la factura
                if ($request->input('force') == 1) {
                    DB::transaction(function () use ($invoiceProductIds, $invoice) {
                        // Eliminar centros de costo de las salidas
                        $outflowIds = Outflow::whereIn('invoice_product_id', $invoiceProductIds)->pluck('id');
                        DB::table('outflow_cost_center')->whereIn('outflow_id', $outflowIds)->delete();
                        // Eliminar las salidas
                        Outflow::whereIn('invoice_product_id', $invoiceProductIds)->delete();
                        // Eliminar la factura (cascada elimina invoice_products)
                        $invoice->delete();
                    });
                    return back()->with('success', 'Factura y sus salidas eliminadas correctamente.');
                }

                return back()->withErrors([
                    'error' => 'Esta factura tiene ' . $outflowCount . ' ' . ($outflowCount === 1 ? 'salida registrada' : 'salidas registradas') . ' asociada' . ($outflowCount === 1 ? '' : 's') . ' (productos: ' . $usedProducts->join(', ') . ').',
                    'outflow_count' => $outflowCount,
                ]);
            }
        }

        $invoice->delete();
    }
}

<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Outflow;
use Illuminate\Support\Facades\DB;
use App\Traits\CheckSeasonLocked;

class DeleteInvoiceController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Invoice $invoice)
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
                return back()->withErrors([
                    'error' => 'No se puede eliminar esta factura porque los siguientes productos ya tienen salidas registradas: ' . $usedProducts->join(', ') . '. Elimine primero las salidas asociadas.'
                ]);
            }
        }

        $invoice->delete();
    }
}

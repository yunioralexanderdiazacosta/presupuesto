<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use App\Models\Invoice;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OutflowsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        // Traer facturas del equipo y temporada actual, con sus productos y proveedor
    $invoices = Invoice::with(['supplier', 'invoiceProducts.product.unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get();

        $rows = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                // Filtro por término de búsqueda
                if ($term && stripos($invoice->number_document, $term) === false) {
                    continue;
                }
                $rows[] = [
                    'invoice_id'        => $invoice->id,
                    'number_document'   => $invoice->number_document,
                    'supplier'          => $invoice->supplier->name ?? '-',
                    'product'           => $invoiceProduct->product->name ?? '-',
                    'unit'              => $invoiceProduct->product->unit->name ?? '-',
                    'quantity'          => $invoiceProduct->quantity ?? $invoiceProduct->amount ?? '-',
                    'invoice_product_id'=> $invoiceProduct->id,
                ];
            }
        }

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Outflows', [
            'outflows' => $paginated,
            'term'     => $term
        ]);
    }
}

<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\TypeDocument;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\Product;
use App\Models\Unit;
use Inertia\Inertia;
use App\Models\Month;
use App\Models\ExpenseReportItem;
use App\Models\PurchaseOrder;

class CreateInvoiceController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $typeDocuments = TypeDocument::select('id', 'name')->get()
            ->map(fn($t) => ['label' => $t->name, 'value' => $t->id]);

        $suppliers = Supplier::where('team_id', $user->team_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['label' => $s->name, 'value' => $s->id]);

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['label' => $c->name, 'value' => $c->id]);

        $products = Product::where('team_id', $user->team_id)
            ->select('id', 'name', 'unit_id')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => ['label' => $p->name, 'value' => $p->id, 'unit_id' => $p->unit_id]);

        $units = Unit::select('id', 'name')->get()
            ->map(fn($u) => ['label' => $u->name, 'value' => $u->id]);

        $months = Month::select('id', 'name')->orderBy('id')->get()
            ->map(fn($m) => ['label' => $m->name, 'value' => $m->id]);

        // Pre-llenado desde item de rendición de gastos
        $prefill = null;
        if ($request->has('expense_item_id')) {
            $item = ExpenseReportItem::with(['supplier:id,name', 'expenseReport:id,number'])
                ->whereHas('expenseReport', function ($q) use ($user) {
                    $q->where('team_id', $user->team_id)
                        ->whereIn('status', ['aprobada', 'pagada']);
                })
                ->find($request->expense_item_id);

            if ($item) {
                $prefill = [
                    'expense_item_id'       => $item->id,
                    'expense_report_number' => $item->expenseReport->number ?? '',
                    'supplier_id'           => $item->supplier_id,
                    'date'                  => $item->date->format('Y-m-d'),
                    'type_document_id'      => $item->type_document_id,
                    'number_document'       => $item->document_number ?? '',
                    'product_name'          => $item->product_name ?? '',
                    'unit_id'               => null,
                    'amount'                => (float) $item->amount,
                    'description'           => $item->description,
                ];
            }
        }

        // Ordenes de compra aprobadas/enviadas/completadas del equipo y temporada
        $purchaseOrders = PurchaseOrder::where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
            ->whereIn('status', ['approved', 'sent', 'received_partial', 'completed'])
            ->with('supplier:id,name')
            ->orderBy('order_date', 'desc')
            ->get()
            ->transform(function($po) {
                return [
                    'label' => $po->order_number . ' - ' . ($po->supplier->name ?? 'Sin proveedor') . ' ($' . number_format($po->total, 0, ',', '.') . ')',
                    'value' => $po->id,
                    'supplier_id' => $po->supplier_id,
                ];
            });

        return Inertia::render('Invoices/Create', compact('typeDocuments', 'suppliers', 'companyReasons', 'products', 'units', 'months', 'prefill', 'purchaseOrders'));
    }
}

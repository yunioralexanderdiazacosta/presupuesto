<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TypeDocument;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Month;
use App\Models\PurchaseOrder;
use App\Models\Branch;
use App\Models\FuelTank;
use App\Models\Level3;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DuplicateInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $user = Auth::user();

        // Seguridad: la factura debe pertenecer al equipo del usuario
        if ($invoice->team_id !== $user->team_id) {
            abort(403);
        }

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

        $purchaseOrders = PurchaseOrder::where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
            ->whereIn('status', ['approved', 'sent', 'received_partial', 'completed'])
            ->with('supplier:id,name')
            ->orderBy('order_date', 'desc')
            ->get()
            ->transform(fn($po) => [
                'label'       => $po->order_number . ' - ' . ($po->supplier->name ?? 'Sin proveedor') . ' ($' . number_format($po->total, 0, ',', '.') . ')',
                'value'       => $po->id,
                'supplier_id' => $po->supplier_id,
            ]);

        $seasonId = session('season_id');
        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => ['label' => $b->name, 'value' => $b->id, 'is_default' => $b->is_default]);

        $defaultBranch = $branches->firstWhere('is_default', true);
        $defaultBranchId = $defaultBranch ? $defaultBranch['value'] : null;

        $fuelTanks = FuelTank::where('team_id', $user->team_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name, 'product_id' => $t->product_id, 'branch_id' => $t->branch_id]);

        $fuelLevel3Ids = Level3::whereHas('level2.level1', fn($q) => $q->where('team_id', $user->team_id))
            ->where('name', 'like', '%combustible%')
            ->pluck('id');
        $fuelProductIds = Product::where('team_id', $user->team_id)
            ->whereIn('level3_id', $fuelLevel3Ids)
            ->pluck('id')
            ->toArray();

        $invoice->load('invoiceProducts.product');

        $prefill = [
            'is_duplicate'      => true,
            'original_id'       => $invoice->id,
            'supplier_id'       => $invoice->supplier_id,
            'company_reason_id' => $invoice->company_reason_id,
            'type_document_id'  => $invoice->type_document_id,
            'payment_type'      => $invoice->payment_type,
            'payment_term'      => $invoice->payment_term,
            'month_id'          => $invoice->month_id,
            'number_document'   => '', // Vacío: el usuario debe ingresar el nuevo N° de documento
            'date'              => now()->format('Y-m-d'),
            'products'          => $invoice->invoiceProducts->map(fn($ip) => [
                'product_id'   => $ip->product_id,
                'unit_id'      => $ip->product->unit_id ?? '',
                'unit_price'   => $ip->unit_price,
                'amount'       => $ip->amount,
                'observations' => $ip->observations ?? '',
                'is_exento'    => $ip->is_exento ?? false,
                'branch_id'    => $ip->branch_id ?? null,
                'tank_id'      => $ip->tank_id ?? null,
            ])->values()->toArray(),
        ];

        return Inertia::render('Invoices/Create', compact(
            'typeDocuments',
            'suppliers',
            'companyReasons',
            'products',
            'units',
            'months',
            'prefill',
            'purchaseOrders',
            'branches',
            'defaultBranchId',
            'fuelTanks',
            'fuelProductIds'
        ));
    }
}

<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\TypeDocument;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\Month;
use App\Models\Product;
use App\Models\Outflow;
use App\Models\PurchaseOrder;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\FuelTank;

class EditInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $user = Auth::user();

        // Cargar relación de rendición si existe
        $invoice->load('expenseReport:id,number');

        $typeDocuments = TypeDocument::get()->transform(function($type){
            return [
                'label' => $type->name,
                'value' => $type->id
            ];
        });

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
         });

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($companyReason){
            return [
                'label' => $companyReason->name,
                'value' => $companyReason->id
            ];
         });

    $products = Product::where('team_id', $user->team_id)->get()->transform(function($product){
            return [
                'label'   => $product->name,
                'value'   => $product->id,
                'unit_id' => $product->unit_id,
            ];
         });

    $invoiceProducts = $invoice->products()->get()->transform(function($product){
            return [
                'invoice_product_id' => $product->pivot->id,
                'product_id'    => $product->id,
                'unit_id'       => $product->unit_id,
                'unit_price'    => $product->pivot->unit_price,
                'amount'        => $product->pivot->amount,
                'observations'  => $product->pivot->observations,
                'is_exento'     => (bool) $product->pivot->is_exento,
                'branch_id'     => $product->pivot->branch_id,
                'tank_id'       => $product->pivot->tank_id ?? null,
            ];
        });

        // Listado de unidades para llenar el select
        $units = \App\Models\Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

        // Listado de meses contables
        $months = Month::orderBy('id')->get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
            ];
        });

        // Identificar productos protegidos (con salidas asociadas)
        $invoiceProductIds = DB::table('invoice_products')
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        $protectedProductIds = [];
        if ($invoiceProductIds->isNotEmpty()) {
            $protectedInvoiceProductIds = Outflow::whereIn('invoice_product_id', $invoiceProductIds)
                ->pluck('invoice_product_id')
                ->unique();

            $protectedProductIds = DB::table('invoice_products')
                ->whereIn('id', $protectedInvoiceProductIds)
                ->pluck('product_id')
                ->values()
                ->toArray();
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

        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
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

        $fuelLevel3Ids = \App\Models\Level3::whereHas('level2.level1', fn($q) => $q->where('team_id', $user->team_id))
            ->where('name', 'like', '%combustible%')
            ->pluck('id');
        $fuelProductIds = Product::where('team_id', $user->team_id)
            ->whereIn('level3_id', $fuelLevel3Ids)
            ->pluck('id')
            ->toArray();

        return Inertia::render('Invoices/Edit', compact(
            'invoice',
            'invoiceProducts',
            'products',
            'units',
            'typeDocuments',
            'suppliers',
            'companyReasons',
            'months',
            'protectedProductIds',
            'purchaseOrders',
            'branches',
            'defaultBranchId',
            'fuelTanks',
            'fuelProductIds'
        ));
    }
}

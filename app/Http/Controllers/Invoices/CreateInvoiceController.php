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

class CreateInvoiceController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

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
        // Unidades disponibles para productos
        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

    return Inertia::render('Invoices/Create', compact('typeDocuments', 'suppliers', 'companyReasons', 'products', 'units'));
    }
}

<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

class DeleteSupplierController extends Controller
{
    public function __invoke(Supplier $supplier)
    {
        $supplier->delete();
    }
}

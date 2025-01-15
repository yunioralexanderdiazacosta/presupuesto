<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class UpdateSupplierController extends Controller
{
    public function __invoke(Supplier $supplier, FormSupplierRequest $request)
    {
        $supplier->name     = $request->name;
        $supplier->rut      = $request->rut;
        $supplier->email    = $request->email;
        $supplier->contact  = $request->contact;
        $supplier->phone    = $request->phone;
        $supplier->save();
    }
}

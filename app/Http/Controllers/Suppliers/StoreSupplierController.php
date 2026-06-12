<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSupplierRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use Illuminate\Http\Request;


class StoreSupplierController extends Controller
{
    public function __invoke(FormSupplierRequest $request)
    {
        $user = Auth::user();

        $supplier = Supplier::Create([
            'name' => strtoupper($request->name),
            'rut' => $request->rut,
            'email' => $request->email,
            'contact' => $request->contact,
            'phone' => $request->phone,
            'team_id' => $user->team_id
        ]);

        // Retornar el proveedor creado en flash para que Inertia lo pase al frontend
        return redirect()->back()->with('supplier', $supplier);
    }
}

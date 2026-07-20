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
        $supplier->name     = strtoupper($request->name);
        $supplier->rut      = $request->rut;
        $supplier->email    = $request->email;
        $supplier->contact  = $request->contact;
        $supplier->phone    = $request->phone;
        $supplier->save();

        // Sincronizar cuentas bancarias: reemplazar por las enviadas
        $supplier->bankAccounts()->delete();
        foreach ($request->accounts ?? [] as $account) {
            $supplier->bankAccounts()->create([
                'bank_id'         => $account['bank_id'],
                'account_type_id' => $account['account_type_id'],
                'account_number'  => $account['account_number'],
            ]);
        }
    }
}

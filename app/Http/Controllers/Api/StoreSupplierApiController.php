<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreSupplierApiController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rut' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'rut')
                    ->where(fn ($query) => $query->where('team_id', $user->team_id)),
            ],
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'accounts'                    => 'nullable|array',
            'accounts.*.bank_id'          => 'required|exists:banks,id',
            'accounts.*.account_type_id'  => 'required|exists:account_types,id',
            'accounts.*.account_number'   => 'required|string|max:30',
        ], [
            'rut.unique' => 'Ya existe un proveedor registrado con este RUT.',
            'accounts.*.bank_id.required'         => 'El banco es obligatorio en cada cuenta.',
            'accounts.*.account_type_id.required' => 'El tipo de cuenta es obligatorio en cada cuenta.',
            'accounts.*.account_number.required'  => 'El número de cuenta es obligatorio en cada cuenta.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Supplier::create([
            'name' => $request->name,
            'rut' => $request->rut,
            'email' => $request->email,
            'contact' => $request->contact,
            'phone' => $request->phone,
            'team_id' => $user->team_id,
        ]);

        foreach ($request->accounts ?? [] as $account) {
            $supplier->bankAccounts()->create([
                'bank_id'         => $account['bank_id'],
                'account_type_id' => $account['account_type_id'],
                'account_number'  => $account['account_number'],
            ]);
        }

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ]
        ]);
    }
}

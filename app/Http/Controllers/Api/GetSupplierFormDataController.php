<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\AccountType;

class GetSupplierFormDataController extends Controller
{
    public function __invoke()
    {
        $banks = Bank::where('active', true)->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name]);

        $accountTypes = AccountType::where('active', true)->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name]);

        return response()->json([
            'banks'        => $banks,
            'accountTypes' => $accountTypes,
        ]);
    }
}

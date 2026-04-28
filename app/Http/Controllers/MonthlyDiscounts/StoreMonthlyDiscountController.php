<?php

namespace App\Http\Controllers\MonthlyDiscounts;

use App\Http\Requests\MonthlyDiscounts\StoreMonthlyDiscountRequest;
use App\Models\MonthlyDiscount;
use Illuminate\Support\Facades\Auth;

class StoreMonthlyDiscountController
{
    public function __invoke(StoreMonthlyDiscountRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;
        $validated['user_id'] = $user->id;

        MonthlyDiscount::create($validated);

        return redirect()->back()->with('success', 'Descuento registrado correctamente.');
    }
}

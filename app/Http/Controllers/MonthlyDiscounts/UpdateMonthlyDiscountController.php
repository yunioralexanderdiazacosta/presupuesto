<?php

namespace App\Http\Controllers\MonthlyDiscounts;

use App\Http\Requests\MonthlyDiscounts\UpdateMonthlyDiscountRequest;
use App\Models\MonthlyDiscount;
use Illuminate\Support\Facades\Auth;

class UpdateMonthlyDiscountController
{
    public function __invoke(UpdateMonthlyDiscountRequest $request, MonthlyDiscount $monthlyDiscount)
    {
        $user = Auth::user();

        if ($monthlyDiscount->team_id !== $user->team_id) {
            abort(403);
        }

        $monthlyDiscount->update($request->validated());

        return redirect()->back()->with('success', 'Descuento actualizado correctamente.');
    }
}

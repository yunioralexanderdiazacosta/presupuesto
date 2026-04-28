<?php

namespace App\Http\Controllers\MonthlyDiscounts;

use App\Models\MonthlyDiscount;
use Illuminate\Support\Facades\Auth;

class DeleteMonthlyDiscountController
{
    public function __invoke(MonthlyDiscount $monthlyDiscount)
    {
        $user = Auth::user();

        if ($monthlyDiscount->team_id !== $user->team_id) {
            abort(403);
        }

        $monthlyDiscount->delete();

        return redirect()->back()->with('success', 'Descuento eliminado correctamente.');
    }
}

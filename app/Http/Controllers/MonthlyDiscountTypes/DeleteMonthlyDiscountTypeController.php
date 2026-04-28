<?php

namespace App\Http\Controllers\MonthlyDiscountTypes;

use App\Models\MonthlyDiscountType;
use Illuminate\Support\Facades\Auth;

class DeleteMonthlyDiscountTypeController
{
    public function __invoke(MonthlyDiscountType $monthlyDiscountType)
    {
        $user = Auth::user();

        if ($monthlyDiscountType->team_id !== $user->team_id) {
            abort(403);
        }

        $monthlyDiscountType->delete();

        return redirect()->back()->with('success', 'Tipo de descuento mensual eliminado correctamente.');
    }
}

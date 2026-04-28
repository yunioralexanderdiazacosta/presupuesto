<?php

namespace App\Http\Controllers\MonthlyDiscountTypes;

use App\Models\MonthlyDiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateMonthlyDiscountTypeController
{
    public function __invoke(Request $request, MonthlyDiscountType $monthlyDiscountType)
    {
        $user = Auth::user();

        if ($monthlyDiscountType->team_id !== $user->team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'is_active' => 'boolean',
        ]);

        $monthlyDiscountType->update($validated);

        return redirect()->back()->with('success', 'Tipo de descuento mensual actualizado correctamente.');
    }
}

<?php

namespace App\Http\Controllers\MonthlyDiscountTypes;

use App\Models\MonthlyDiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreMonthlyDiscountTypeController
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede exceder 150 caracteres.',
        ]);

        $validated['team_id'] = $user->team_id;

        MonthlyDiscountType::create($validated);

        return redirect()->back()->with('success', 'Tipo de descuento mensual registrado correctamente.');
    }
}

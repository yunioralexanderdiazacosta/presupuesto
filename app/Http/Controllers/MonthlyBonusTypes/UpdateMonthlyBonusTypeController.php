<?php

namespace App\Http\Controllers\MonthlyBonusTypes;

use App\Models\MonthlyBonusType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateMonthlyBonusTypeController
{
    public function __invoke(Request $request, MonthlyBonusType $monthlyBonusType)
    {
        $user = Auth::user();

        if ($monthlyBonusType->team_id !== $user->team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'is_active' => 'boolean',
        ]);

        $monthlyBonusType->update($validated);

        return redirect()->back()->with('success', 'Tipo de bono mensual actualizado correctamente.');
    }
}

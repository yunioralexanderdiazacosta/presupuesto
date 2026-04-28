<?php

namespace App\Http\Controllers\MonthlyBonusTypes;

use App\Models\MonthlyBonusType;
use Illuminate\Support\Facades\Auth;

class DeleteMonthlyBonusTypeController
{
    public function __invoke(MonthlyBonusType $monthlyBonusType)
    {
        $user = Auth::user();

        if ($monthlyBonusType->team_id !== $user->team_id) {
            abort(403);
        }

        $monthlyBonusType->delete();

        return redirect()->back()->with('success', 'Tipo de bono mensual eliminado correctamente.');
    }
}

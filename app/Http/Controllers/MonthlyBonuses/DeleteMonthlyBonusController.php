<?php

namespace App\Http\Controllers\MonthlyBonuses;

use App\Models\MonthlyBonus;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteMonthlyBonusController
{
    public function __invoke(MonthlyBonus $monthlyBonus)
    {
        $user = Auth::user();

        if ($monthlyBonus->team_id !== $user->team_id) {
            abort(403);
        }

        $monthlyBonus->delete();

        return redirect()->back()->with('success', 'Bono eliminado correctamente.');
    }
}

<?php

namespace App\Http\Controllers\MonthlyBonuses;

use App\Http\Requests\MonthlyBonuses\UpdateMonthlyBonusRequest;
use App\Models\MonthlyBonus;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class UpdateMonthlyBonusController
{
    public function __invoke(UpdateMonthlyBonusRequest $request, MonthlyBonus $monthlyBonus)
    {
        $user = Auth::user();

        if ($monthlyBonus->team_id !== $user->team_id) {
            abort(403);
        }

        $validated = $request->validated();
        $costCenterIds = $validated['cost_center_ids'];
        unset($validated['cost_center_ids']);

        $monthlyBonus->update($validated);
        $monthlyBonus->costCenters()->sync($costCenterIds);

        return redirect()->back()->with('success', 'Bono actualizado correctamente.');
    }
}

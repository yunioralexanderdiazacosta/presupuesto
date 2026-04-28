<?php

namespace App\Http\Controllers\MonthlyBonuses;

use App\Http\Requests\MonthlyBonuses\StoreMonthlyBonusRequest;
use App\Models\MonthlyBonus;
use Illuminate\Support\Facades\Auth;

class StoreMonthlyBonusController
{
    public function __invoke(StoreMonthlyBonusRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $costCenterIds = $validated['cost_center_ids'];
        unset($validated['cost_center_ids']);

        $validated['team_id'] = $user->team_id;
        $validated['user_id'] = $user->id;

        $bonus = MonthlyBonus::create($validated);
        $bonus->costCenters()->sync($costCenterIds);

        return redirect()->back()->with('success', 'Bono registrado correctamente.');
    }
}

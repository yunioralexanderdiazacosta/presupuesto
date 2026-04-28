<?php

namespace App\Http\Controllers\MonthlyBonusTypes;

use App\Http\Requests\MonthlyBonusTypes\StoreMonthlyBonusTypeRequest;
use App\Models\MonthlyBonusType;
use Illuminate\Support\Facades\Auth;

class StoreMonthlyBonusTypeController
{
    public function __invoke(StoreMonthlyBonusTypeRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;

        MonthlyBonusType::create($validated);

        return redirect()->back()->with('success', 'Tipo de bono mensual registrado correctamente.');
    }
}

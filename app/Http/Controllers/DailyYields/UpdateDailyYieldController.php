<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyYields\UpdateDailyYieldRequest;
use App\Models\DailyYield;

class UpdateDailyYieldController extends Controller
{
    public function __invoke(UpdateDailyYieldRequest $request, DailyYield $dailyYield)
    {
        $dailyYield->update([
            'labor_type_id' => $request->labor_type_id,
            'rate' => $request->rate,
            'quantity' => $request->quantity,
            'amount' => $request->rate * $request->quantity,
            'hours' => $request->hours,
            'bonus_type_id' => $request->bonus_type_id,
            'bonus_amount' => $request->bonus_amount ?? 0,
            'cost_center_id' => $request->cost_center_id,
            'observations' => $request->observations,
        ]);

        return redirect()->back()
            ->with('success', 'Tarja actualizada.');
    }
}

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
            'payment_type' => $request->payment_type,
            'labor_type_id' => $request->labor_type_id,
            'labor_rate_id' => $request->payment_type === 'trato' ? $request->labor_rate_id : null,
            'rate' => $request->rate,
            'quantity' => $request->quantity,
            'amount' => $request->rate * $request->quantity,
            'workdays' => $request->workdays,
            'bonus_type_id' => $request->bonus_type_id,
            'bonus_amount' => $request->bonus_amount ?? 0,
            'target_price' => $request->target_price ?? null,
            'target_price_bonus' => $request->target_price_bonus ?? null,
            'observations' => $request->observations,
        ]);

        // Reemplazar centros de costo en pivote
        $dailyYield->costCenters()->delete();
        if (!empty($request->cost_center_ids)) {
            foreach ($request->cost_center_ids as $ccId) {
                $dailyYield->costCenters()->create(['cost_center_id' => $ccId]);
            }
        }

        return redirect()->back()
            ->with('success', 'Tarja actualizada.');
    }
}

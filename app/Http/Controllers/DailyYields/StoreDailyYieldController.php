<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyYields\StoreDailyYieldRequest;
use App\Models\DailyYield;
use Illuminate\Support\Facades\Auth;

class StoreDailyYieldController extends Controller
{
    public function __invoke(StoreDailyYieldRequest $request)
    {
        $user = Auth::user();

        DailyYield::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'payment_type' => $request->payment_type,
            'labor_type_id' => $request->labor_type_id,
            'labor_rate_id' => $request->payment_type === 'trato' ? $request->labor_rate_id : null,
            'rate' => $request->rate,
            'quantity' => $request->quantity,
            'amount' => $request->rate * $request->quantity,
            'hours' => $request->hours,
            'bonus_type_id' => $request->bonus_type_id,
            'bonus_amount' => $request->bonus_amount ?? 0,
            'target_price' => $request->target_price ?? null,
            'target_price_bonus' => $request->target_price_bonus ?? null,
            'cost_center_id' => $request->cost_center_id,
            'team_id' => $user->team_id,
            'season_id' => session('season_id'),
            'user_id' => $user->id,
            'observations' => $request->observations,
        ]);

        return redirect()->back()
            ->with('success', 'Tarja registrada correctamente.');
    }
}

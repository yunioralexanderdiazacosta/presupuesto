<?php

namespace App\Http\Controllers\OvertimeHours;

use App\Http\Requests\OvertimeHours\StoreOvertimeHourRequest;
use App\Models\Contract;
use App\Models\OvertimeHour;
use App\Models\OvertimeType;
use Illuminate\Support\Facades\Auth;

class StoreOvertimeHourController
{
    public function __invoke(StoreOvertimeHourRequest $request)
    {
        $user      = Auth::user();
        $validated = $request->validated();

        $costCenterIds = $validated['cost_center_ids'];
        unset($validated['cost_center_ids']);

        // Capturar snapshots al momento del registro
        $contract    = Contract::find($validated['contract_id']);
        $overtimeType = OvertimeType::find($validated['overtime_type_id']);

        $validated['base_salary_snapshot']         = $contract?->base_salary;
        $validated['hourly_rate_factor_snapshot']  = $overtimeType?->hourly_rate_factor;
        $validated['overtime_multiplier_snapshot'] = $overtimeType?->overtime_multiplier;

        $validated['team_id'] = $user->team_id;
        $validated['user_id'] = $user->id;

        $overtimeHour = OvertimeHour::create($validated);
        $overtimeHour->costCenters()->sync($costCenterIds);

        return redirect()->back()->with('success', 'Hora extra registrada correctamente.');
    }
}

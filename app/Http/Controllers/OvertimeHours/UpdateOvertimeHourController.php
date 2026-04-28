<?php

namespace App\Http\Controllers\OvertimeHours;

use App\Http\Requests\OvertimeHours\UpdateOvertimeHourRequest;
use App\Models\Contract;
use App\Models\OvertimeHour;
use App\Models\OvertimeType;
use Illuminate\Support\Facades\Auth;

class UpdateOvertimeHourController
{
    public function __invoke(UpdateOvertimeHourRequest $request, OvertimeHour $overtimeHour)
    {
        $user      = Auth::user();
        $validated = $request->validated();

        abort_if($overtimeHour->team_id !== $user->team_id, 403);

        $costCenterIds = $validated['cost_center_ids'];
        unset($validated['cost_center_ids']);

        // Re-capturar snapshots al editar (el usuario está corrigiendo el registro)
        $contract     = Contract::find($validated['contract_id']);
        $overtimeType = OvertimeType::find($validated['overtime_type_id']);

        $validated['base_salary_snapshot']         = $contract?->base_salary;
        $validated['hourly_rate_factor_snapshot']  = $overtimeType?->hourly_rate_factor;
        $validated['overtime_multiplier_snapshot'] = $overtimeType?->overtime_multiplier;

        $overtimeHour->update($validated);
        $overtimeHour->costCenters()->sync($costCenterIds);

        return redirect()->back()->with('success', 'Hora extra actualizada correctamente.');
    }
}

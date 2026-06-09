<?php

namespace App\Http\Controllers\OvertimeHours;

use App\Models\OvertimeHour;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteOvertimeHourController
{
    public function __invoke(OvertimeHour $overtimeHour)
    {
        $user = Auth::user();

        abort_if($overtimeHour->team_id !== $user->team_id, 403);

        $overtimeHour->costCenters()->detach();
        $overtimeHour->delete();

        return redirect()->back()->with('success', 'Hora extra eliminada correctamente.');
    }
}

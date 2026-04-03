<?php

namespace App\Http\Controllers\DailyAttendances;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyAttendances\StoreDailyAttendanceRequest;
use App\Models\DailyAttendance;
use Illuminate\Support\Facades\Auth;

class StoreDailyAttendanceController extends Controller
{
    public function __invoke(StoreDailyAttendanceRequest $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        foreach ($request->attendances as $att) {
            DailyAttendance::updateOrCreate(
                [
                    'employee_id' => $att['employee_id'],
                    'date' => $request->date,
                    'team_id' => $user->team_id,
                ],
                [
                    'is_present' => $att['is_present'],
                    'estimated_labor_type_id' => $request->estimated_labor_type_id,
                    'estimated_cost_center_id' => $request->estimated_cost_center_id,
                    'season_id' => $seasonId,
                    'registered_by' => $user->id,
                ]
            );
        }

        return redirect()->route('daily-attendances.index', ['date' => $request->date])
            ->with('success', 'Asistencia registrada correctamente.');
    }
}

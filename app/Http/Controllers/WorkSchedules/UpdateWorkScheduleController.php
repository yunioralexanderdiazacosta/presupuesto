<?php

namespace App\Http\Controllers\WorkSchedules;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateWorkScheduleController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'monday_hours' => 'required|numeric|min:0|max:24',
            'tuesday_hours' => 'required|numeric|min:0|max:24',
            'wednesday_hours' => 'required|numeric|min:0|max:24',
            'thursday_hours' => 'required|numeric|min:0|max:24',
            'friday_hours' => 'required|numeric|min:0|max:24',
            'saturday_hours' => 'required|numeric|min:0|max:24',
            'sunday_hours' => 'required|numeric|min:0|max:24',
        ]);

        $user = Auth::user();
        $seasonId = session('season_id');

        $weeklyHours = $request->monday_hours + $request->tuesday_hours
            + $request->wednesday_hours + $request->thursday_hours
            + $request->friday_hours + $request->saturday_hours
            + $request->sunday_hours;

        WorkSchedule::updateOrCreate(
            ['team_id' => $user->team_id, 'season_id' => $seasonId],
            [
                'monday_hours' => $request->monday_hours,
                'tuesday_hours' => $request->tuesday_hours,
                'wednesday_hours' => $request->wednesday_hours,
                'thursday_hours' => $request->thursday_hours,
                'friday_hours' => $request->friday_hours,
                'saturday_hours' => $request->saturday_hours,
                'sunday_hours' => $request->sunday_hours,
                'weekly_hours' => round($weeklyHours, 1),
            ]
        );

        return redirect()->back()
            ->with('success', 'Horario actualizado correctamente.');
    }
}

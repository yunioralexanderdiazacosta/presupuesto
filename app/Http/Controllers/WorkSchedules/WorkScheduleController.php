<?php

namespace App\Http\Controllers\WorkSchedules;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        $schedule = WorkSchedule::firstOrCreate(
            ['team_id' => $user->team_id, 'season_id' => $seasonId],
            [
                'monday_hours' => 8.0,
                'tuesday_hours' => 8.0,
                'wednesday_hours' => 8.0,
                'thursday_hours' => 8.0,
                'friday_hours' => 8.0,
                'saturday_hours' => 0.0,
                'sunday_hours' => 0.0,
                'weekly_hours' => 40.0,
            ]
        );

        return Inertia::render('WorkSchedules/Index', [
            'schedule' => $schedule,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\FormScheduleRequest;
use App\Models\Schedule;

class UpdateScheduleController extends Controller
{
    public function __invoke(Schedule $schedule, FormScheduleRequest $request)
    {
        $schedule->name = $request->name;
        $schedule->is_active = $request->is_active ?? true;
        $schedule->save();
    }
}

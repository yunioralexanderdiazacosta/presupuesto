<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\FormScheduleRequest;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

class StoreScheduleController extends Controller
{
    public function __invoke(FormScheduleRequest $request)
    {
        $user = Auth::user();

        Schedule::create([
            'name' => $request->name,
            'is_active' => $request->is_active ?? true,
            'team_id' => $user->team_id,
        ]);
    }
}

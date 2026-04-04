<?php

namespace App\Http\Controllers\DailyAttendances;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeleteDailyAttendanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $date = $request->date;

        DailyAttendance::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->delete();

        return redirect()->back()
            ->with('success', 'Asistencia del día eliminada.');
    }
}

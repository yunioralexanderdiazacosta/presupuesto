<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleApiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return Schedule::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150']);
        $user = Auth::user();

        $schedule = Schedule::create([
            'team_id' => $user->team_id,
            'name' => $request->name,
        ]);

        return response()->json(['id' => $schedule->id, 'name' => $schedule->name]);
    }

    public function destroy(Schedule $schedule)
    {
        if ($schedule->team_id !== Auth::user()->team_id) {
            abort(403);
        }

        $schedule->delete();
        return response()->json(['success' => true]);
    }
}

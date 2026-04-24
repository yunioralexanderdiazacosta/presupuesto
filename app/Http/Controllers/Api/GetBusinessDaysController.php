<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VacationCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetBusinessDaysController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ]);

        $user    = Auth::user();
        $service = new VacationCalculatorService();

        $days = $service->countBusinessDays(
            Carbon::parse($request->start),
            Carbon::parse($request->end),
            $user->team_id
        );

        return response()->json(['business_days' => $days]);
    }
}

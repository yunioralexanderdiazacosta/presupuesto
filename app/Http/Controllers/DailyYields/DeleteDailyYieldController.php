<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Models\DailyYield;

use App\Traits\CheckSeasonLocked;

class DeleteDailyYieldController extends Controller
{
    public function __invoke(DailyYield $dailyYield)
    {
        $date = $dailyYield->date->format('Y-m-d');
        $dailyYield->delete();

        return redirect()->back()
            ->with('success', 'Tarja eliminada.');
    }
}

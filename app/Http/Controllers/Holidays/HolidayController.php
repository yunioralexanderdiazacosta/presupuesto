<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $teamId = $user->team_id;

        $national = Holiday::whereNull('team_id')
            ->orderBy('date')
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'date'         => $h->date->format('Y-m-d'),
                'date_label'   => $h->date->format('d/m/Y'),
                'name'         => $h->name,
                'is_recurring' => $h->is_recurring,
            ]);

        $teamHolidays = Holiday::where('team_id', $teamId)
            ->orderBy('date')
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'date'         => $h->date->format('Y-m-d'),
                'date_label'   => $h->date->format('d/m/Y'),
                'name'         => $h->name,
                'is_recurring' => $h->is_recurring,
            ]);

        return Inertia::render('Holidays/Index', [
            'nationalHolidays' => $national,
            'teamHolidays'     => $teamHolidays,
        ]);
    }
}

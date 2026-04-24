<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreHolidayController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'name'         => 'required|string|max:255',
            'is_recurring' => 'boolean',
        ]);

        $user = Auth::user();

        Holiday::create([
            'team_id'      => $user->team_id,
            'date'         => $request->date,
            'name'         => $request->name,
            'is_recurring' => $request->boolean('is_recurring', false),
        ]);

        return redirect()->route('holidays.index')
            ->with('success', 'Feriado agregado correctamente.');
    }
}

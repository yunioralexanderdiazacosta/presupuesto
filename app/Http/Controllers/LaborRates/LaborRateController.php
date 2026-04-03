<?php

namespace App\Http\Controllers\LaborRates;

use App\Http\Controllers\Controller;
use App\Models\LaborRate;
use App\Models\LaborType;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LaborRateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        $laborRates = LaborRate::with(['laborType', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->orderBy('name')
            ->get();

        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($lt) => [
                'value' => $lt->id,
                'label' => $lt->name,
            ]);

        $units = Unit::orderBy('name')->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => $u->name,
        ]);

        return Inertia::render('LaborRates/Index', [
            'laborRates' => $laborRates,
            'laborTypes' => $laborTypes,
            'units' => $units,
        ]);
    }
}

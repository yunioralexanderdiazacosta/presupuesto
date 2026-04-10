<?php

namespace App\Http\Controllers\LaborTypes;

use App\Http\Controllers\Controller;
use App\Models\LaborType;
use App\Models\Level3;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LaborTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $laborTypes = LaborType::with(['level3', 'unit'])
            ->where('team_id', $user->team_id)
            ->orderBy('code')
            ->get();

        $seasonId = session('season_id');

        $level3s = Level3::from('level3s as l3')
            ->join('level2s as l2', 'l2.id', 'l3.level2_id')
            ->join('level1s as l1', 'l1.id', 'l2.level1_id')
            ->select('l3.id', 'l3.name')
            ->where('l1.team_id', $user->team_id)
            ->where('l1.season_id', $seasonId)
            ->where('l2.name', 'mano de obra')
            ->orderBy('l3.name')
            ->get()
            ->map(fn($l) => [
                'value' => $l->id,
                'label' => $l->name,
            ]);

        $units = Unit::orderBy('name')->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => $u->name,
        ]);

        return Inertia::render('LaborTypes/Index', [
            'laborTypes' => $laborTypes,
            'level3s' => $level3s,
            'units' => $units,
        ]);
    }
}

<?php

namespace App\Http\Controllers\LaborRates;

use App\Http\Controllers\Controller;
use App\Models\LaborRate;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowLaborRatesCatalogController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $laborRates = LaborRate::with(['laborType', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($lr) => [
                'id' => $lr->id,
                'code' => $lr->code,
                'name' => $lr->name,
                'rate' => $lr->rate,
                'labor_type_name' => $lr->laborType?->name ?? 'Sin labor asociada',
                'unit_name' => $lr->unit?->name ?? '-',
            ]);

        $teamName = $user->currentTeam?->name ?? 'Equipo';

        return Inertia::render('LaborRates/Show', [
            'laborRates' => $laborRates,
            'teamName' => $teamName,
        ]);
    }
}

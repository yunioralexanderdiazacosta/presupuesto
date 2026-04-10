<?php

namespace App\Http\Controllers\LaborTypes;

use App\Http\Controllers\Controller;
use App\Models\LaborType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowLaborTypesCatalogController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $laborTypes = LaborType::with(['level3', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($lt) => [
                'id' => $lt->id,
                'code' => $lt->code,
                'name' => $lt->name,
                'level3_name' => $lt->level3?->name ?? 'Sin clasificación',
                'unit_name' => $lt->unit?->name ?? '-',
                'default_rate' => $lt->default_rate,
                'default_bonus' => $lt->default_bonus,
            ]);

        $teamName = $user->currentTeam?->name ?? 'Equipo';

        return Inertia::render('LaborTypes/Show', [
            'laborTypes' => $laborTypes,
            'teamName' => $teamName,
        ]);
    }
}

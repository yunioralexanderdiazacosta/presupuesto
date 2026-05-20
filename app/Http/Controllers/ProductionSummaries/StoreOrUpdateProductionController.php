<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreOrUpdateProductionController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $validated = $request->validate([
            'fruit_id'          => 'required|exists:fruits,id',
            'notes'             => 'nullable|string|max:500',
            'advances'          => 'nullable|array',
            'advances.*.name'   => 'required|string|max:255',
            'advances.*.amount' => 'required|numeric|min:0',
            'discounts'          => 'nullable|array',
            'discounts.*.name'   => 'required|string|max:255',
            'discounts.*.amount' => 'required|numeric|min:0',
        ]);

        $production = Production::updateOrCreate(
            [
                'season_id' => $season_id,
                'team_id'   => $user->team_id,
                'fruit_id'  => $validated['fruit_id'],
            ],
            [
                'notes' => $validated['notes'] ?? null,
            ]
        );

        // Sincronizar ajustes: eliminar todos e insertar los nuevos por tipo
        $production->advances()->delete();
        foreach ($validated['advances'] ?? [] as $adv) {
            $production->advances()->create([
                'type'   => 'advance',
                'name'   => $adv['name'],
                'amount' => $adv['amount'],
            ]);
        }
        foreach ($validated['discounts'] ?? [] as $disc) {
            $production->advances()->create([
                'type'   => 'discount',
                'name'   => $disc['name'],
                'amount' => $disc['amount'],
            ]);
        }

        $production->load('advances');

        return response()->json(['success' => true, 'production' => $production]);
    }
}

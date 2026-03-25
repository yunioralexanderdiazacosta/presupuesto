<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\KgYieldCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpsertKgYieldCostsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'costs'            => 'required|array',
            'costs.*.kg_ha'    => 'required|numeric|min:0',
            'costs.*.cost_usd' => 'required|numeric|min:0',
        ]);

        // Reemplazar todos los registros del equipo
        KgYieldCost::where('team_id', $user->team_id)->delete();

        $records = collect($data['costs'])->map(fn($c) => [
            'team_id'  => $user->team_id,
            'kg_ha'    => $c['kg_ha'],
            'cost_usd' => $c['cost_usd'],
        ])->toArray();

        KgYieldCost::insert($records);

        return back();
    }
}

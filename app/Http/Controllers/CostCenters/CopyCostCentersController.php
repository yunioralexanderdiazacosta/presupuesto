<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CopyCostCentersController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'source_season_id' => 'required|integer',
        ]);

        $user          = Auth::user();
        $teamId        = $user->team_id;
        $targetSeasonId = session('season_id');

        // Verificar que la season activa exista y pertenezca al equipo
        $targetSeason = Season::where('id', $targetSeasonId)
            ->where('team_id', $teamId)
            ->firstOrFail();

        // Verificar que la season origen también pertenezca al equipo
        $sourceSeason = Season::where('id', $request->source_season_id)
            ->where('team_id', $teamId)
            ->firstOrFail();

        if ($sourceSeason->id === $targetSeason->id) {
            return response()->json(['message' => 'La temporada origen y destino no pueden ser la misma.'], 422);
        }

        // Cuarteles origen
        $sourceCostCenters = CostCenter::where('season_id', $sourceSeason->id)
            ->get();

        if ($sourceCostCenters->isEmpty()) {
            return response()->json(['message' => 'La temporada origen no tiene centros de costo.'], 422);
        }

        $copiedCostCenters   = 0;
        $copiedVarieties     = 0;

        DB::transaction(function () use ($sourceCostCenters, $targetSeason, $teamId, &$copiedCostCenters, &$copiedVarieties) {
            foreach ($sourceCostCenters as $src) {
                // Crear el cuartel en la temporada destino
                $newCostCenter = CostCenter::create([
                    'name'                 => $src->name,
                    'surface'              => $src->surface,
                    'season_id'            => $targetSeason->id,
                    'fruit_id'             => $src->fruit_id,
                    'variety_id'           => $src->variety_id,
                    'parcel_id'            => $src->parcel_id,
                    'year_plantation'      => $src->year_plantation,
                    'development_state_id' => $src->development_state_id,
                    'company_reason_id'    => $src->company_reason_id,
                    'status'               => $src->status,
                ]);
                $copiedCostCenters++;

                // Copiar las variedades del cuartel (cost_center_varieties)
                $varieties = DB::table('cost_center_varieties')
                    ->where('cost_center_id', $src->id)
                    ->get();

                foreach ($varieties as $v) {
                    DB::table('cost_center_varieties')->insert([
                        'cost_center_id'       => $newCostCenter->id,
                        'season_id'            => $targetSeason->id,
                        'variety_id'           => $v->variety_id,
                        'fruit_id'             => $v->fruit_id,
                        'rootstock_id'         => $v->rootstock_id,
                        'development_state_id' => $v->development_state_id,
                        'surface'              => $v->surface,
                        'year_plantation'      => $v->year_plantation,
                        'observations'         => $v->observations,
                        'team_id'              => $teamId,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                    $copiedVarieties++;
                }
            }
        });

        return response()->json([
            'success'          => true,
            'copied_cost_centers' => $copiedCostCenters,
            'copied_varieties'    => $copiedVarieties,
        ]);
    }
}

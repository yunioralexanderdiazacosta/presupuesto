<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\Season;
use App\Services\BudgetCopyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CopyBudgetController extends Controller
{
    public function __invoke(Request $request, Season $season, BudgetCopyService $copyService)
    {
        $request->validate([
            'target_season_id' => 'required|integer|different:' . $season->id,
            'types'            => 'required|array|min:1',
            'types.*'          => ['string', Rule::in(array_keys(BudgetCopyService::TYPES))],
        ]);

        $teamId = Auth::user()->team_id;

        // Verificar que ambas temporadas pertenezcan al equipo del usuario
        $sourceSeason = Season::where('id', $season->id)
            ->where('team_id', $teamId)
            ->firstOrFail();

        $targetSeason = Season::where('id', $request->target_season_id)
            ->where('team_id', $teamId)
            ->firstOrFail();

        // La copia entre temporadas parte de la versión oficial y llega a la oficial de la temporada destino
        $sourceVersion = BudgetVersion::where('season_id', $sourceSeason->id)->official()->first();
        abort_if(!$sourceVersion, 422, 'La temporada origen no tiene una versión oficial de presupuesto.');

        $output = DB::transaction(function () use ($copyService, $sourceVersion, $targetSeason, $teamId, $request) {
            $targetVersion = BudgetVersion::where('season_id', $targetSeason->id)->official()->first()
                ?? BudgetVersion::create([
                    'season_id'   => $targetSeason->id,
                    'team_id'     => $teamId,
                    'name'        => 'Original',
                    'is_official' => true,
                    'created_by'  => Auth::id(),
                ]);

            return $copyService->copy($sourceVersion, $targetSeason, $targetVersion, $request->types);
        });

        return response()->json([
            'success'  => true,
            'results'  => $output['results'],
            'warnings' => $output['warnings'],
        ]);
    }
}

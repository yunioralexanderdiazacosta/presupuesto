<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CostCenter;
use App\Models\Fruit;
use App\Models\Variety;


class SidebarController extends Controller
   {
    /**
     * Verifica si existen registros en varieties para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasVarietyForSeason(Request $request)
    {
        $seasonId = $request->input('season_id');
        $teamId = null;
        if ($seasonId) {
            $season = \App\Models\Season::find($seasonId);
            if ($season) {
                $teamId = $season->team_id;
            }
        }
        $count = 0;
        if ($teamId) {
            $count = \App\Models\Variety::where('team_id', $teamId)->count();
        }
        $exists = $count > 0;
        return response()->json(['exists' => $exists]);
    }

    /**
     * Verifica si existen registros en fruits para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasFruitForSeason(Request $request)
    {
        $seasonId = $request->input('season_id');
        $teamId = null;
        if ($seasonId) {
            $season = \App\Models\Season::find($seasonId);
            if ($season) {
                $teamId = $season->team_id;
            }
        }
        $count = 0;
        if ($teamId) {
            $count = Fruit::where('team_id', $teamId)->count();
        }
        $exists = $count > 0;
        return response()->json(['exists' => $exists]);
    }
    /**
     * Verifica si existen registros en costcenters para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasCostCenterForSeason(Request $request)
    {
        $seasonId = $request->input('season_id');
        $count = CostCenter::where('season_id', $seasonId)->count();
        $exists = $count > 0;
        return response()->json(['exists' => $exists]);
    }
}

   
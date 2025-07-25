<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CostCenter;
use App\Models\Fruit;
use App\Models\Variety;
use Illuminate\Support\Facades\Auth;

class SidebarController extends Controller
   {




    /**
     * Verifica si existen registros en varieties para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasVarietyForSeason(Request $request)
    {
        // Usar siempre el team_id del usuario autenticado
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            $exists = Variety::where('team_id', $teamId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }

    /**
     * Verifica si existen registros en fruits para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasFruitForSeason(Request $request)
    {
        // Usar siempre el team_id del usuario autenticado
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            $exists = Fruit::where('team_id', $teamId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }
    /**
     * Verifica si existen registros en costcenters para un season_id dado.
     * @param Request $request (espera season_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasCostCenterForSeason(Request $request)
    {
        // Usar el season_id de sesión si existe, si no, usar el del request
        $seasonId = session('season_id') ?? $request->input('season_id');
        $exists = false;
        if ($seasonId) {
            $exists = CostCenter::where('season_id', $seasonId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }


        /**
     * Verifica si existen registros en company_reasons para el team_id del usuario autenticado.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasCompanyReasonForTeam(Request $request)
    {
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            $exists = \App\Models\CompanyReason::where('team_id', $teamId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }


        /**
     * Verifica si existen registros en seasons para el team_id del usuario autenticado.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasSeasonForTeam(Request $request)
    {
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            $exists = \App\Models\Season::where('team_id', $teamId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }

        /**
     * Verifica si existen registros en parcels para el team_id del usuario autenticado.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasParcelForTeam(Request $request)
    {
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            $exists = \App\Models\Parcel::where('team_id', $teamId)->exists();
        }
        return response()->json(['exists' => $exists]);
    }

    /**
     * Verifica si existen registros en level3s para un level2 dado,
     * validando la cadena level2 → level1 → team_id del usuario.
     * @param Request $request (espera level2_id como parámetro GET o POST)
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasLevel3ForLevel2(Request $request)
    {
        $user = Auth::user();
        $teamId = $user ? $user->team_id : null;
        $exists = false;
        if ($teamId) {
            // Buscar todos los level2 cuyo level1 sea del team
            $level2Ids = \App\Models\Level2::whereIn('level1_id', function($query) use ($teamId) {
                $query->select('id')
                    ->from('level1s')
                    ->where('team_id', $teamId);
            })->pluck('id');
            if ($level2Ids->count() > 0) {
                $exists = \App\Models\Level3::whereIn('level2_id', $level2Ids)->exists();
            }
        }
        return response()->json(['exists' => $exists]);
    }
}


<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reparte las guías de despacho entre sus cuarteles.
 *
 * Una guía puede llevar fruta de varios cuarteles. Los kg despachados se conocen por cuartel;
 * los kg del proceso (recepción, exportación, clasificación) se registran por lote y se reparten
 * entre los cuarteles de la guía en proporción a sus kg despachados.
 */
class DispatchAllocation
{
    /** Fracción de la guía que aportó el cuartel (columna `pdcc`), para usar en SQL. */
    public const SHARE_SQL = 'pdcc.kg_dispatched / NULLIF(production_dispatches.kg_dispatched, 0)';

    /**
     * Una fila por guía y cuartel con los kg ya repartidos (kg_received y kg_exported son la parte del cuartel).
     *
     * kg_lots es lo recepcionado por el packing (suma de kg_received de los lotes de la guía); es 0 mientras la guía no tenga lotes.
     *
     * @return Collection<int, object{ccv_id:int, dispatch_id:int, status:string, kg_dispatched:float, kg_lots:float, kg_received:float, kg_exported:float}>
     */
    public static function forSeason(int $teamId, int $seasonId): Collection
    {
        $share = self::SHARE_SQL;

        return DB::table('production_dispatch_cost_centers as pdcc')
            ->join('production_dispatches', 'production_dispatches.id', '=', 'pdcc.production_dispatch_id')
            ->where('production_dispatches.team_id', $teamId)
            ->where('production_dispatches.season_id', $seasonId)
            ->selectRaw("
                pdcc.cost_center_variety_id as ccv_id,
                production_dispatches.id as dispatch_id,
                production_dispatches.status as status,
                pdcc.kg_dispatched as kg_dispatched,
                COALESCE((SELECT SUM(pl.kg_received) FROM production_lots pl WHERE pl.production_dispatch_id = production_dispatches.id), 0) * {$share} as kg_lots,
                COALESCE(production_dispatches.kg_received, 0) * {$share} as kg_received,
                COALESCE(production_dispatches.kg_exported, 0) * {$share} as kg_exported
            ")
            ->get();
    }
}

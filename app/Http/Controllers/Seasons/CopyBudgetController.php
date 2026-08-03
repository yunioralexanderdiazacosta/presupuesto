<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\CostCenter;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\Supply;
use App\Models\Service;
use App\Models\Harvest;
use App\Models\ManPower;
use App\Models\Administration;
use App\Models\Field;
use App\Models\Level2;
use App\Models\Level3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CopyBudgetController extends Controller
{
    public function __invoke(Request $request, Season $season)
    {
        $request->validate([
            'target_season_id' => 'required|integer|different:' . $season->id,
            'types'            => 'required|array|min:1',
            'types.*'          => 'in:agrochemicals,fertilizers,supplies,services,harvests,manpowers,administrations,fields',
        ]);

        $user     = Auth::user();
        $teamId   = $user->team_id;

        // Verificar que ambas temporadas pertenezcan al equipo del usuario
        $sourceSeason = Season::where('id', $season->id)
            ->where('team_id', $teamId)
            ->firstOrFail();

        $targetSeason = Season::where('id', $request->target_season_id)
            ->where('team_id', $teamId)
            ->firstOrFail();

        $types   = $request->types;
        $results = [];
        $warnings = [];

        DB::transaction(function () use ($sourceSeason, $targetSeason, $teamId, $types, &$results, &$warnings) {

            // --- 1. Construir mapa de CostCenters: nombre => id en destino ---
            $targetCostCenters = CostCenter::where('season_id', $targetSeason->id)
                ->get(['id', 'name'])
                ->keyBy(fn($cc) => strtolower(trim($cc->name)));

            $sourceCostCenters = CostCenter::where('season_id', $sourceSeason->id)
                ->get(['id', 'name']);

            // mapa: id_origen => id_destino
            $costCenterMap = [];
            foreach ($sourceCostCenters as $src) {
                $key = strtolower(trim($src->name));
                if (isset($targetCostCenters[$key])) {
                    $costCenterMap[$src->id] = $targetCostCenters[$key]->id;
                } else {
                    $warnings[] = "Cuartel \"{$src->name}\" no existe en la temporada destino — sus items no serán copiados.";
                }
            }

            // --- 2. Construir mapa de Level3 (subfamily): nombre => id en destino ---
            $targetLevel3s = Level3::whereHas('level2.level1', function ($q) use ($targetSeason, $teamId) {
                $q->where('season_id', $targetSeason->id)->where('team_id', $teamId);
            })->get(['id', 'name'])->keyBy(fn($l) => strtolower(trim($l->name)));

            $sourceLevel3s = Level3::whereHas('level2.level1', function ($q) use ($sourceSeason, $teamId) {
                $q->where('season_id', $sourceSeason->id)->where('team_id', $teamId);
            })->get(['id', 'name']);

            // mapa: id_origen => id_destino
            $level3Map = [];
            foreach ($sourceLevel3s as $src) {
                $key = strtolower(trim($src->name));
                if (isset($targetLevel3s[$key])) {
                    $level3Map[$src->id] = $targetLevel3s[$key]->id;
                } else {
                    $warnings[] = "Subfamilia \"{$src->name}\" no encontrada en temporada destino — insumos con esta subfamilia se copiarán sin categoría.";
                }
            }

            // level2_id fue eliminado de la tabla fields (migración 2025_06_25)
            // No se necesita mapa de Level2 para Fields

            // --- 4. Copiar según tipos seleccionados ---

            if (in_array('agrochemicals', $types)) {
                $result = $this->copyAgrochemicals($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['agrochemicals'] = $result;
            }

            if (in_array('fertilizers', $types)) {
                $result = $this->copyFertilizers($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['fertilizers'] = $result;
            }

            if (in_array('supplies', $types)) {
                $result = $this->copySupplies($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['supplies'] = $result;
            }

            if (in_array('services', $types)) {
                $result = $this->copyServices($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['services'] = $result;
            }

            if (in_array('harvests', $types)) {
                $result = $this->copyHarvests($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['harvests'] = $result;
            }

            if (in_array('manpowers', $types)) {
                $result = $this->copyManPowers($sourceSeason, $targetSeason, $costCenterMap, $level3Map, $warnings);
                $results['manpowers'] = $result;
            }

            if (in_array('administrations', $types)) {
                $result = $this->copyAdministrations($sourceSeason, $targetSeason, $level3Map, $warnings);
                $results['administrations'] = $result;
            }

            if (in_array('fields', $types)) {
                $result = $this->copyFields($sourceSeason, $targetSeason, $level3Map, $warnings);
                $results['fields'] = $result;
            }
        });

        // Eliminar advertencias duplicadas
        $warnings = array_values(array_unique($warnings));

        return response()->json([
            'success'  => true,
            'results'  => $results,
            'warnings' => $warnings,
        ]);
    }

    // -------------------------------------------------------------------------
    // Agroquímicos
    // -------------------------------------------------------------------------
    private function copyAgrochemicals(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = Agrochemical::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Agroquímico \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Agrochemical::create([
                'product_name'  => $item->product_name,
                'price'         => $item->price,
                'dose'          => $item->dose,
                'observations'  => $item->observations,
                'mojamiento'    => $item->mojamiento,
                'unit_id'       => $item->unit_id,
                'unit_id_price' => $item->unit_id_price,
                'subfamily_id'  => $newSubfamilyId,
                'dose_type_id'  => $item->dose_type_id,
                'team_id'       => $target->team_id,
                'season_id'     => $target->id,
                'user_id'       => $item->user_id,
            ]);
            $copied++;

            // Copiar pivot agrochemical_items
            $pivots = DB::table('agrochemical_items')
                ->where('agrochemical_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table('agrochemical_items')->insert([
                    'agrochemical_id' => $new->id,
                    'cost_center_id'  => $ccMap[$pivot->cost_center_id],
                    'month_id'        => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Fertilizantes
    // -------------------------------------------------------------------------
    private function copyFertilizers(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = Fertilizer::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Fertilizante \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Fertilizer::create([
                'product_name'  => $item->product_name,
                'price'         => $item->price,
                'dose'          => $item->dose,
                'observations'  => $item->observations,
                'unit_id'       => $item->unit_id,
                'unit_id_price' => $item->unit_id_price,
                'subfamily_id'  => $newSubfamilyId,
                'team_id'       => $target->team_id,
                'season_id'     => $target->id,
                'user_id'       => $item->user_id,
            ]);
            $copied++;

            $pivots = DB::table('fertilizer_items')
                ->where('fertilizer_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table('fertilizer_items')->insert([
                    'fertilizer_id'  => $new->id,
                    'cost_center_id' => $ccMap[$pivot->cost_center_id],
                    'month_id'       => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Insumos (Supplies)
    // -------------------------------------------------------------------------
    private function copySupplies(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = Supply::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Insumo \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Supply::create([
                'product_name'  => $item->product_name,
                'price'         => $item->price,
                'quantity'      => $item->quantity,
                'observations'  => $item->observations,
                'unit_id'       => $item->unit_id,
                'unit_id_price' => $item->unit_id_price,
                'subfamily_id'  => $newSubfamilyId,
                'team_id'       => $target->team_id,
                'season_id'     => $target->id,
                'user_id'       => $item->user_id,
            ]);
            $copied++;

            $supplyItemsTable = DB::getSchemaBuilder()->hasTable('supply_items') ? 'supply_items' : 'supplies_items';
            $pivots = DB::table($supplyItemsTable)
                ->where('supply_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table($supplyItemsTable)->insert([
                    'supply_id'      => $new->id,
                    'cost_center_id' => $ccMap[$pivot->cost_center_id],
                    'month_id'       => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Servicios
    // -------------------------------------------------------------------------
    private function copyServices(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = Service::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Servicio \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Service::create([
                'product_name'  => $item->product_name,
                'price'         => $item->price,
                'quantity'      => $item->quantity,
                'observations'  => $item->observations,
                'unit_id'       => $item->unit_id,
                'unit_id_price' => $item->unit_id_price,
                'subfamily_id'  => $newSubfamilyId,
                'team_id'       => $target->team_id,
                'season_id'     => $target->id,
                'user_id'       => $item->user_id,
            ]);
            $copied++;

            $pivots = DB::table('service_items')
                ->where('service_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table('service_items')->insert([
                    'service_id'     => $new->id,
                    'cost_center_id' => $ccMap[$pivot->cost_center_id],
                    'month_id'       => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Cosecha (Harvests)
    // -------------------------------------------------------------------------
    private function copyHarvests(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = Harvest::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Cosecha \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Harvest::create([
                'product_name'  => $item->product_name,
                'price'         => $item->price,
                'quantity'      => $item->quantity,
                'observations'  => $item->observations,
                'unit_id'       => $item->unit_id,
                'unit_id_price' => $item->unit_id_price,
                'subfamily_id'  => $newSubfamilyId,
                'team_id'       => $target->team_id,
                'season_id'     => $target->id,
                'user_id'       => $item->user_id,
            ]);
            $copied++;

            $pivots = DB::table('harvest_items')
                ->where('harvest_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table('harvest_items')->insert([
                    'harvest_id'     => $new->id,
                    'cost_center_id' => $ccMap[$pivot->cost_center_id],
                    'month_id'       => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Mano de Obra (ManPower)
    // -------------------------------------------------------------------------
    private function copyManPowers(Season $source, Season $target, array $ccMap, array $l3Map, array &$warnings): array
    {
        $items = ManPower::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Mano de obra \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = ManPower::create([
                'product_name' => $item->product_name,
                'price'        => $item->price,
                'workday'      => $item->workday,
                'observations' => $item->observations,
                'subfamily_id' => $newSubfamilyId,
                'unit_id'      => $item->unit_id,
                'team_id'      => $target->team_id,
                'season_id'    => $target->id,
                'user_id'      => $item->user_id,
            ]);
            $copied++;

            $pivots = DB::table('manpower_items')
                ->where('man_power_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                if (!isset($ccMap[$pivot->cost_center_id])) {
                    $itemsSkipped++;
                    continue;
                }
                DB::table('manpower_items')->insert([
                    'man_power_id'   => $new->id,
                    'cost_center_id' => $ccMap[$pivot->cost_center_id],
                    'month_id'       => $pivot->month_id,
                ]);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped];
    }

    // -------------------------------------------------------------------------
    // Administración (sin cost_center_id en items)
    // -------------------------------------------------------------------------
    private function copyAdministrations(Season $source, Season $target, array $l3Map, array &$warnings): array
    {
        $items = Administration::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Administración \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Administration::create([
                'product_name' => $item->product_name,
                'price'        => $item->price,
                'quantity'     => $item->quantity,
                'observations' => $item->observations,
                'unit_id'      => $item->unit_id,
                'subfamily_id' => $newSubfamilyId,
                'team_id'      => $target->team_id,
                'season_id'    => $target->id,
                'user_id'      => $item->user_id,
            ]);
            $copied++;

            // administration_items no tiene cost_center_id — copia directa
            $pivots = DB::table('administration_items')
                ->where('administration_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                $row = (array) $pivot;
                unset($row['id']);
                $row['administration_id'] = $new->id;
                DB::table('administration_items')->insert($row);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => 0];
    }

    // -------------------------------------------------------------------------
    // Campos (Fields — tiene level2_id extra, sin cost_center_id en items)
    // -------------------------------------------------------------------------
    private function copyFields(Season $source, Season $target, array $l3Map, array &$warnings): array
    {
        $items = Field::where('season_id', $source->id)->get();
        $copied = 0;
        $itemsCopied = 0;

        foreach ($items as $item) {
            $newSubfamilyId = $l3Map[$item->subfamily_id] ?? null;
            if ($item->subfamily_id && !isset($l3Map[$item->subfamily_id])) {
                $warnings[] = "Campo \"{$item->product_name}\": subfamilia no encontrada en temporada destino, se copió sin subfamilia.";
            }

            $new = Field::create([
                'product_name' => $item->product_name,
                'price'        => $item->price,
                'quantity'     => $item->quantity,
                'observations' => $item->observations,
                'unit_id'      => $item->unit_id,
                'subfamily_id' => $newSubfamilyId,
                'team_id'      => $target->team_id,
                'season_id'    => $target->id,
                'user_id'      => $item->user_id,
            ]);
            $copied++;

            // field_items no tiene cost_center_id — copia directa (tiene id propio)
            $pivots = DB::table('field_items')
                ->where('field_id', $item->id)
                ->get();

            foreach ($pivots as $pivot) {
                $row = (array) $pivot;
                unset($row['id']);
                $row['field_id'] = $new->id;
                DB::table('field_items')->insert($row);
                $itemsCopied++;
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => 0];
    }
}

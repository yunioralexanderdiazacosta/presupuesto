<?php

namespace App\Services;

use App\Models\Administration;
use App\Models\Agrochemical;
use App\Models\Branch;
use App\Models\BudgetVersion;
use App\Models\CostCenter;
use App\Models\Fertilizer;
use App\Models\Field;
use App\Models\Harvest;
use App\Models\Investment;
use App\Models\Level3;
use App\Models\ManPower;
use App\Models\Season;
use App\Models\Service;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Copia las líneas de presupuesto de una versión hacia otra versión.
 *
 * - Misma temporada: todos los ids (cuarteles, subfamilias, sucursales e inversiones) se conservan tal cual.
 * - Otra temporada: se traducen por nombre a los equivalentes de la temporada destino.
 *
 * Cada línea se clona con replicate(), de modo que ninguna columna (operación, inversión,
 * sucursal, etc.) se pierde al copiar. No abre transacción: la abre quien la invoca.
 */
class BudgetCopyService
{
    public const TYPES = [
        'agrochemicals'   => ['model' => Agrochemical::class,   'label' => 'Agroquímico',    'pivot' => 'agrochemical_items',   'fk' => 'agrochemical_id',   'by_cost_center' => true],
        'fertilizers'     => ['model' => Fertilizer::class,     'label' => 'Fertilizante',   'pivot' => 'fertilizer_items',     'fk' => 'fertilizer_id',     'by_cost_center' => true],
        'supplies'        => ['model' => Supply::class,         'label' => 'Insumo',         'pivot' => 'supply_items',         'fk' => 'supply_id',         'by_cost_center' => true],
        'services'        => ['model' => Service::class,        'label' => 'Servicio',       'pivot' => 'service_items',        'fk' => 'service_id',        'by_cost_center' => true],
        'harvests'        => ['model' => Harvest::class,        'label' => 'Cosecha',        'pivot' => 'harvest_items',        'fk' => 'harvest_id',        'by_cost_center' => true],
        'manpowers'       => ['model' => ManPower::class,       'label' => 'Mano de obra',   'pivot' => 'manpower_items',       'fk' => 'man_power_id',      'by_cost_center' => true],
        'administrations' => ['model' => Administration::class, 'label' => 'Administración', 'pivot' => 'administration_items', 'fk' => 'administration_id', 'by_cost_center' => false],
        'fields'          => ['model' => Field::class,          'label' => 'Campo',          'pivot' => 'field_items',          'fk' => 'field_id',          'by_cost_center' => false],
    ];

    /**
     * @param  string[]  $types  Claves de self::TYPES a copiar
     * @return array{results: array, warnings: string[]}
     */
    public function copy(BudgetVersion $source, Season $targetSeason, BudgetVersion $targetVersion, array $types): array
    {
        if ($source->id === $targetVersion->id) {
            throw new InvalidArgumentException('La versión origen y destino no pueden ser la misma.');
        }

        if ((int) $targetVersion->season_id !== (int) $targetSeason->id) {
            throw new InvalidArgumentException('La versión destino no pertenece a la temporada destino.');
        }

        $sourceSeason = $source->season;
        $sameSeason   = (int) $sourceSeason->id === (int) $targetSeason->id;
        $warnings     = [];

        // Misma temporada: no hay nada que traducir, todos los ids (cuarteles, subfamilias, etc.) se conservan tal cual
        $maps = $sameSeason ? null : [
            'cost_center' => $this->costCenterMap($sourceSeason, $targetSeason, $warnings),
            'level3'      => $this->level3Map($sourceSeason, $targetSeason, $warnings),
            'investment'  => $this->investmentMap($sourceSeason, $targetSeason),
            'branch'      => $this->branchMap($sourceSeason, $targetSeason),
        ];

        $results = [];
        foreach (self::TYPES as $type => $config) {
            if (in_array($type, $types, true)) {
                $results[$type] = $this->copyType($config, $source, $targetSeason, $targetVersion, $maps, $warnings);
            }
        }

        return [
            'results'  => $results,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function copyType(array $config, BudgetVersion $source, Season $targetSeason, BudgetVersion $targetVersion, ?array $maps, array &$warnings): array
    {
        // Se lee la versión de origen aunque no sea la activa del usuario
        $lines = $config['model']::withoutGlobalScopes()
            ->where('season_id', $source->season_id)
            ->where('budget_version_id', $source->id)
            ->get();

        $copied = 0;
        $itemsCopied = 0;
        $itemsSkipped = 0;
        $linesSkipped = 0;

        foreach ($lines as $line) {
            $label = "{$config['label']} \"{$line->product_name}\"";

            // subfamily_id es obligatorio: sin equivalente en la temporada destino la línea no se puede copiar
            $subfamilyId = $line->subfamily_id;
            if ($maps !== null) {
                $subfamilyId = $line->subfamily_id ? ($maps['level3'][$line->subfamily_id] ?? null) : null;
                if (!$subfamilyId) {
                    $warnings[] = "{$label}: subfamilia no encontrada en temporada destino, la línea NO se copió.";
                    $linesSkipped++;
                    continue;
                }
            }

            $new = $line->replicate();
            $new->team_id           = $targetSeason->team_id;
            $new->season_id         = $targetSeason->id;
            $new->budget_version_id = $targetVersion->id;
            $new->subfamily_id      = $subfamilyId;

            if ($maps !== null) {
                $new->investment_id = $this->translate($line->investment_id, $maps['investment'], "{$label}: inversión no encontrada en temporada destino, se copió sin inversión.", $warnings);

                // Solo Administración y Generales de Campo tienen sucursal
                if (array_key_exists('branch_id', $line->getAttributes())) {
                    $new->branch_id = $this->translate($line->branch_id, $maps['branch'], "{$label}: sucursal no encontrada en temporada destino, se copió sin sucursal.", $warnings);
                }
            }

            $new->save();
            $copied++;

            $rows = [];
            foreach (DB::table($config['pivot'])->where($config['fk'], $line->id)->get() as $pivot) {
                $row = (array) $pivot;
                unset($row['id']);
                $row[$config['fk']] = $new->id;

                if ($maps !== null && $config['by_cost_center']) {
                    if (!isset($maps['cost_center'][$pivot->cost_center_id])) {
                        $itemsSkipped++;
                        continue;
                    }
                    $row['cost_center_id'] = $maps['cost_center'][$pivot->cost_center_id];
                }

                $rows[] = $row;
            }

            if ($rows) {
                DB::table($config['pivot'])->insert($rows);
                $itemsCopied += count($rows);
            }
        }

        return ['copied' => $copied, 'items_copied' => $itemsCopied, 'items_skipped' => $itemsSkipped, 'lines_skipped' => $linesSkipped];
    }

    private function translate($sourceId, array $map, string $warning, array &$warnings)
    {
        if (!$sourceId) {
            return null;
        }

        if (!isset($map[$sourceId])) {
            $warnings[] = $warning;
            return null;
        }

        return $map[$sourceId];
    }

    private function costCenterMap(Season $source, Season $target, array &$warnings): array
    {
        $sourceRows = CostCenter::where('season_id', $source->id)->get(['id', 'name']);

        $targetByName = CostCenter::where('season_id', $target->id)->get(['id', 'name'])
            ->keyBy(fn($cc) => $this->key($cc->name));

        $map = [];
        foreach ($sourceRows as $row) {
            $match = $targetByName[$this->key($row->name)] ?? null;
            if ($match) {
                $map[$row->id] = $match->id;
            } else {
                $warnings[] = "Cuartel \"{$row->name}\" no existe en la temporada destino — sus items no serán copiados.";
            }
        }

        return $map;
    }

    private function level3Map(Season $source, Season $target, array &$warnings): array
    {
        $levels = fn(Season $season) => Level3::whereHas('level2.level1', function ($q) use ($season) {
            $q->where('season_id', $season->id)->where('team_id', $season->team_id);
        })->get(['id', 'name']);

        $sourceRows = $levels($source);

        $targetByName = $levels($target)->keyBy(fn($l) => $this->key($l->name));

        $map = [];
        foreach ($sourceRows as $row) {
            $match = $targetByName[$this->key($row->name)] ?? null;
            if ($match) {
                $map[$row->id] = $match->id;
            } else {
                $warnings[] = "Subfamilia \"{$row->name}\" no encontrada en temporada destino — las líneas con esta subfamilia no se copiarán.";
            }
        }

        return $map;
    }

    private function investmentMap(Season $source, Season $target): array
    {
        $sourceRows = Investment::where('season_id', $source->id)->get(['id', 'name']);

        $targetByName = Investment::where('season_id', $target->id)->get(['id', 'name'])
            ->keyBy(fn($i) => $this->key($i->name));

        $map = [];
        foreach ($sourceRows as $row) {
            if ($match = $targetByName[$this->key($row->name)] ?? null) {
                $map[$row->id] = $match->id;
            }
        }

        return $map;
    }

    private function branchMap(Season $source, Season $target): array
    {
        $sourceRows = Branch::where('season_id', $source->id)->get(['id', 'name']);

        $targetByName = Branch::where('season_id', $target->id)->get(['id', 'name'])
            ->keyBy(fn($b) => $this->key($b->name));

        $map = [];
        foreach ($sourceRows as $row) {
            if ($match = $targetByName[$this->key($row->name)] ?? null) {
                $map[$row->id] = $match->id;
            }
        }

        return $map;
    }

    private function key(?string $name): string
    {
        return strtolower(trim((string) $name));
    }
}

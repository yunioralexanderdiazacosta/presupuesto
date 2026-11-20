<?php

namespace App\Services;

use App\Models\ProductionDispatch;
use App\Models\ProductionLot;
use App\Models\ProductionProcess;
use App\Support\ProportionalSplit;
use Illuminate\Support\Facades\DB;

/**
 * Guarda un proceso del packing y reparte (prorratea) sus kg entre los lotes participantes
 * en proporción a los kg que cada lote aporta. El reparto queda guardado por lote para que
 * guías, cuarteles y análisis sigan leyendo datos por lote.
 */
class ProductionProcessService
{
    private const KG_FIELDS = ['kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'];

    /**
     * @param  array{process_number:string, process_date:string, kg_exported?:mixed, kg_national?:mixed, kg_industrial?:mixed, kg_waste?:mixed, lots:array<int, array{id:int, kg:float}>, items?:array}  $data
     */
    public function save(?ProductionProcess $process, array $data, int $teamId, int $seasonId): ProductionProcess
    {
        return DB::transaction(function () use ($process, $data, $teamId, $seasonId) {
            $previousLotIds = $process ? $process->allocations()->pluck('production_lot_id')->all() : [];

            $weights = [];
            foreach ($data['lots'] as $lot) {
                $weights[(int) $lot['id']] = (float) $lot['kg'];
            }

            $header = [
                'process_number' => $data['process_number'],
                'process_date' => $data['process_date'],
                'kg_to_process' => round(array_sum($weights), 2),
            ];
            foreach (self::KG_FIELDS as $field) {
                $header[$field] = (float) ($data[$field] ?? 0);
            }

            if ($process) {
                $process->allocations()->delete();
                $process->items()->delete();
                $process->update($header);
            } else {
                $process = ProductionProcess::create($header + ['team_id' => $teamId, 'season_id' => $seasonId]);
            }

            $lots = ProductionLot::whereIn('id', array_keys($weights))->get()->keyBy('id');

            $shares = [];
            foreach (self::KG_FIELDS as $field) {
                $shares[$field] = ProportionalSplit::split($header[$field], $weights);
            }

            $itemShares = [];
            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $itemShares[$index] = [
                    'kg' => ProportionalSplit::split((float) ($item['kg'] ?? 0), $weights),
                    'boxes' => isset($item['boxes']) ? ProportionalSplit::split((float) $item['boxes'], $weights, 0) : null,
                ];
            }

            foreach ($weights as $lotId => $kg) {
                $lot = $lots[$lotId];

                $process->allocations()->create(
                    ['production_lot_id' => $lotId, 'kg_to_process' => $kg]
                    + array_map(fn($byLot) => $byLot[$lotId], $shares)
                );

                foreach (array_values($data['items'] ?? []) as $index => $item) {
                    $process->items()->create([
                        'production_dispatch_id' => $lot->production_dispatch_id,
                        'production_lot_id' => $lotId,
                        'classification_type' => $item['classification_type'],
                        'classification_value' => $item['classification_value'],
                        'kg' => $itemShares[$index]['kg'][$lotId],
                        'boxes' => $itemShares[$index]['boxes'] !== null ? (int) $itemShares[$index]['boxes'][$lotId] : null,
                    ]);
                }
            }

            $this->syncLots(array_unique(array_merge($previousLotIds, array_keys($weights))));

            return $process;
        });
    }

    public function delete(ProductionProcess $process): void
    {
        DB::transaction(function () use ($process) {
            $lotIds = $process->allocations()->pluck('production_lot_id')->all();
            $process->allocations()->delete();
            $process->items()->delete();
            $process->delete();
            $this->syncLots($lotIds);
        });
    }

    /**
     * Tras eliminar lotes o guías: los procesos pierden la parte de esos lotes.
     * Se recalculan sus totales con los lotes restantes; si no queda ninguno se elimina el proceso.
     */
    public function refreshAfterLotsRemoved(array $processIds): void
    {
        foreach (ProductionProcess::whereIn('id', $processIds)->get() as $process) {
            $allocations = $process->allocations()->get();

            if ($allocations->isEmpty()) {
                $process->delete();
                continue;
            }

            $process->update(
                ['kg_to_process' => round((float) $allocations->sum('kg_to_process'), 2)]
                + collect(self::KG_FIELDS)->mapWithKeys(fn($f) => [$f => round((float) $allocations->sum($f), 2)])->all()
            );
        }
    }

    private function syncLots(array $lotIds): void
    {
        $lots = ProductionLot::whereIn('id', $lotIds)->get();
        $lots->each->syncFromProcesses();

        ProductionDispatch::whereIn('id', $lots->pluck('production_dispatch_id')->unique())->get()
            ->each->syncFromLots();
    }
}

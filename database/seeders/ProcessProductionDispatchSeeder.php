<?php

namespace Database\Seeders;

use App\Models\FruitClassificationType;
use App\Models\ProductionDispatch;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessProductionDispatchSeeder extends Seeder
{
    private array $teamIds = [1, 2, 5, 6];

    /** Cuántos de los 10 despachos SEED se procesan por team (el resto queda "despachado"). */
    private int $processPerTeam = 10;

    /** Valores genéricos por si el team no tiene clasificaciones cargadas para el frutal. */
    private array $fallbackCalibers = ['J', 'XL', 'L', 'M', 'S'];

    public function run(): void
    {
        foreach ($this->teamIds as $teamId) {
            $dispatches = ProductionDispatch::with('costCenterVariety')
                ->where('team_id', $teamId)
                ->where('guide_number', 'like', 'SEED-%')
                ->whereIn('status', ['dispatched', 'processed'])
                ->orderBy('id')
                ->limit($this->processPerTeam)
                ->get();

            if ($dispatches->isEmpty()) {
                $this->command->info("Team {$teamId}: sin despachos pendientes de procesar, se omite.");
                continue;
            }

            foreach ($dispatches as $dispatch) {
                $this->processDispatch($teamId, $dispatch);
            }

            $this->command->info("Team {$teamId}: {$dispatches->count()} despachos procesados.");
        }
    }

    private function processDispatch(int $teamId, ProductionDispatch $dispatch): void
    {
        $kgDispatched = (float) $dispatch->kg_dispatched;

        // Lote único: lo despachado entra completo a proceso.
        $kgReceived = $kgDispatched;

        // Reparto: export ~70%, nacional ~15%, industrial ~8%, merma = resto (cierra exacto).
        $kgExported = round($kgReceived * 0.70, 2);
        $kgNational = round($kgReceived * 0.15, 2);
        $kgIndustrial = round($kgReceived * 0.08, 2);
        $kgWaste = round($kgReceived - $kgExported - $kgNational - $kgIndustrial, 2);
        if ($kgWaste < 0) {
            $kgWaste = 0;
        }

        // Calibres a usar: del catálogo del team para el frutal, o genéricos.
        $fruitId = $dispatch->costCenterVariety->fruit_id ?? null;
        $calibers = FruitClassificationType::where('team_id', $teamId)
            ->where('type', 'caliber')
            ->when($fruitId, fn($q) => $q->where('fruit_id', $fruitId))
            ->orderBy('sort_order')
            ->pluck('value')
            ->toArray();

        if (empty($calibers)) {
            $calibers = $this->fallbackCalibers;
        }

        // Usar 3-4 calibres y repartir los kg exportados entre ellos.
        shuffle($calibers);
        $calibers = array_slice($calibers, 0, min(rand(3, 4), count($calibers)));
        $items = $this->buildItems($calibers, $kgExported);

        DB::transaction(function () use ($dispatch, $kgReceived, $kgExported, $kgNational, $kgIndustrial, $kgWaste, $items) {
            $dispatch->update([
                'status' => 'processed',
                'process_date' => Carbon::parse($dispatch->dispatch_date)->addDays(rand(1, 5))->toDateString(),
                'kg_received' => $kgReceived,
                'kg_exported' => $kgExported,
                'kg_national' => $kgNational,
                'kg_industrial' => $kgIndustrial,
                'kg_waste' => $kgWaste,
            ]);

            $dispatch->items()->delete();
            foreach ($items as $item) {
                $dispatch->items()->create($item);
            }
        });
    }

    /**
     * Reparte $totalKg entre los calibres dados, con boxes y percentage proporcionales.
     */
    private function buildItems(array $calibers, float $totalKg): array
    {
        $n = count($calibers);
        if ($n === 0 || $totalKg <= 0) {
            return [];
        }

        // Pesos aleatorios para un reparto no uniforme.
        $weights = [];
        $sumWeights = 0;
        foreach ($calibers as $c) {
            $w = rand(1, 5);
            $weights[] = $w;
            $sumWeights += $w;
        }

        $items = [];
        $assigned = 0;
        foreach ($calibers as $i => $value) {
            $isLast = ($i === $n - 1);
            $kg = $isLast
                ? round($totalKg - $assigned, 2)
                : round($totalKg * ($weights[$i] / $sumWeights), 2);
            $assigned += $kg;

            $items[] = [
                'classification_type' => 'caliber',
                'classification_value' => (string) $value,
                'kg' => $kg,
                'percentage' => round($kg / $totalKg * 100, 2),
                'boxes' => (int) round($kg / 8.2), // cajas de 8,2 kg aprox.
            ];
        }

        return $items;
    }
}

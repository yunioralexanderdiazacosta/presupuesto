<?php

namespace Database\Seeders;

use App\Models\BinType;
use App\Models\BoxType;
use App\Models\Carrier;
use App\Models\CostCenterVariety;
use App\Models\Exporter;
use App\Models\PackingHouse;
use App\Models\ProductionDispatch;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductionDispatchSeeder extends Seeder
{
    /**
     * Teams a poblar (los que ya tienen cuarteles-variedad).
     */
    private array $teamIds = [1, 2, 5, 6];

    private int $dispatchesPerTeam = 10;

    private array $exporterNames = [
        ['name' => 'Exportadora Río Blanco', 'rut' => '76.123.456-7', 'contact' => 'Juan Pérez'],
        ['name' => 'Copefrut S.A.', 'rut' => '85.555.200-4', 'contact' => 'María Soto'],
        ['name' => 'Subsole S.A.', 'rut' => '96.556.940-5', 'contact' => 'Pedro Rojas'],
    ];

    private array $packingNames = [
        ['name' => 'Packing Central', 'address' => 'Camino Los Nogales 1200, San Fernando'],
        ['name' => 'Packing San Vicente', 'address' => 'Ruta H-30 Km 5, San Vicente'],
    ];

    private array $binNames = ['Bin plástico 400 kg', 'Bin madera 300 kg'];
    private array $boxNames = ['Caja 5 kg', 'Caja 8,2 kg'];
    private array $carrierNames = ['Transportes del Valle', 'Fruta Express Ltda.'];
    private array $drivers = ['Luis Muñoz', 'Carlos Díaz', 'Andrés Fuentes', 'Jorge Castro', 'Manuel Reyes'];

    public function run(): void
    {
        foreach ($this->teamIds as $teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                $this->command->warn("Team {$teamId} no existe, se omite.");
                continue;
            }

            // Season objetivo: la más reciente que tenga cuarteles-variedad.
            $seasonId = CostCenterVariety::where('team_id', $teamId)
                ->orderByDesc('season_id')
                ->value('season_id');

            if (!$seasonId) {
                $this->command->warn("Team {$teamId} ({$team->name}) sin cuarteles-variedad, se omite.");
                continue;
            }

            $ccvIds = CostCenterVariety::where('team_id', $teamId)
                ->where('season_id', $seasonId)
                ->pluck('id')
                ->toArray();

            if (empty($ccvIds)) {
                $this->command->warn("Team {$teamId} sin cuarteles en season {$seasonId}, se omite.");
                continue;
            }

            // Idempotencia: no volver a sembrar si ya existen despachos de ejemplo.
            $alreadySeeded = ProductionDispatch::where('team_id', $teamId)
                ->where('guide_number', 'like', 'SEED-%')
                ->exists();
            if ($alreadySeeded) {
                $this->command->info("Team {$teamId} ({$team->name}) ya tiene despachos de ejemplo, se omite.");
                continue;
            }

            // === Catálogos (idempotentes por team) ===
            $exporterIds = $this->ensureExporters($teamId);
            $packingIds = $this->ensurePackings($teamId);
            $binIds = $this->ensureBinTypes($teamId);
            $boxIds = $this->ensureBoxTypes($teamId);
            $carrierIds = $this->ensureCarriers($teamId);

            // === Despachos ===
            for ($i = 1; $i <= $this->dispatchesPerTeam; $i++) {
                $useBins = rand(0, 1) === 1;
                $useBoxes = !$useBins || rand(0, 1) === 1;

                ProductionDispatch::create([
                    'cost_center_variety_id' => $ccvIds[array_rand($ccvIds)],
                    'exporter_id' => $exporterIds[array_rand($exporterIds)],
                    'packing_house_id' => $packingIds[array_rand($packingIds)],
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'dispatch_date' => Carbon::now()->subDays(rand(1, 120))->toDateString(),
                    'guide_number' => sprintf('SEED-%d-%03d', $teamId, $i),
                    'lot_number' => sprintf('L%d-%04d', $teamId, rand(1000, 9999)),
                    'kg_dispatched' => rand(500, 8000) + (rand(0, 99) / 100),
                    'bin_type_id' => $useBins ? $binIds[array_rand($binIds)] : null,
                    'bins_quantity' => $useBins ? rand(5, 40) : null,
                    'box_type_id' => $useBoxes ? $boxIds[array_rand($boxIds)] : null,
                    'boxes_quantity' => $useBoxes ? rand(50, 600) : null,
                    'carrier_id' => $carrierIds[array_rand($carrierIds)],
                    'driver' => $this->drivers[array_rand($this->drivers)],
                    'license_plate' => sprintf('%s%s-%02d', chr(rand(65, 90)), chr(rand(65, 90)), rand(10, 99)),
                    'observations' => rand(0, 1) ? 'Despacho de ejemplo generado automáticamente.' : null,
                    'status' => 'dispatched',
                ]);
            }

            $this->command->info("Team {$teamId} ({$team->name}): {$this->dispatchesPerTeam} despachos creados en season {$seasonId}.");
        }
    }

    private function ensureExporters(int $teamId): array
    {
        foreach ($this->exporterNames as $data) {
            Exporter::firstOrCreate(
                ['team_id' => $teamId, 'name' => $data['name']],
                ['rut' => $data['rut'], 'contact' => $data['contact']]
            );
        }
        return Exporter::where('team_id', $teamId)->pluck('id')->toArray();
    }

    private function ensurePackings(int $teamId): array
    {
        foreach ($this->packingNames as $data) {
            PackingHouse::firstOrCreate(
                ['team_id' => $teamId, 'name' => $data['name']],
                ['address' => $data['address']]
            );
        }
        return PackingHouse::where('team_id', $teamId)->pluck('id')->toArray();
    }

    private function ensureBinTypes(int $teamId): array
    {
        foreach ($this->binNames as $name) {
            BinType::firstOrCreate(
                ['team_id' => $teamId, 'name' => $name],
                ['is_active' => true]
            );
        }
        return BinType::where('team_id', $teamId)->pluck('id')->toArray();
    }

    private function ensureBoxTypes(int $teamId): array
    {
        foreach ($this->boxNames as $name) {
            BoxType::firstOrCreate(
                ['team_id' => $teamId, 'name' => $name],
                ['is_active' => true]
            );
        }
        return BoxType::where('team_id', $teamId)->pluck('id')->toArray();
    }

    private function ensureCarriers(int $teamId): array
    {
        foreach ($this->carrierNames as $name) {
            Carrier::firstOrCreate(
                ['team_id' => $teamId, 'name' => $name],
                ['is_active' => true]
            );
        }
        return Carrier::where('team_id', $teamId)->pluck('id')->toArray();
    }
}

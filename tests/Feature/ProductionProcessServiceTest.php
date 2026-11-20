<?php

namespace Tests\Feature;

use App\Models\ProductionDispatch;
use App\Models\ProductionLot;
use App\Models\ProductionProcess;
use App\Services\ProductionProcessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionProcessServiceTest extends TestCase
{
    private ProductionProcessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Base en memoria con solo las tablas del módulo (las migraciones del proyecto son para MySQL)
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('production_dispatches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('season_id');
            $t->string('status')->default('dispatched');
            $t->date('process_date')->nullable();
            foreach (['kg_dispatched', 'kg_received', 'kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'] as $c) {
                $t->decimal($c, 12, 2)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('production_lots', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('production_dispatch_id');
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('season_id');
            $t->string('lot_number');
            $t->date('reception_date')->nullable();
            $t->decimal('kg_received', 12, 2);
            $t->text('observations')->nullable();
            $t->string('status')->default('received');
            $t->date('process_date')->nullable();
            foreach (['kg_to_process', 'kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'] as $c) {
                $t->decimal($c, 12, 2)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('production_processes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('season_id');
            $t->string('process_number');
            $t->date('process_date');
            foreach (['kg_to_process', 'kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'] as $c) {
                $t->decimal($c, 12, 2)->default(0);
            }
            $t->timestamps();
        });
        Schema::create('production_process_lots', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('production_process_id');
            $t->unsignedBigInteger('production_lot_id');
            foreach (['kg_to_process', 'kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'] as $c) {
                $t->decimal($c, 12, 2)->default(0);
            }
            $t->timestamps();
        });
        Schema::create('production_dispatch_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('production_dispatch_id');
            $t->unsignedBigInteger('production_lot_id');
            $t->unsignedBigInteger('production_process_id')->nullable();
            $t->string('classification_type');
            $t->string('classification_value');
            $t->decimal('kg', 12, 2);
            $t->decimal('percentage', 5, 2)->nullable();
            $t->integer('boxes')->nullable();
            $t->timestamps();
        });

        $this->service = new ProductionProcessService();
    }

    private function lot(int $dispatchId, string $number, float $kg): ProductionLot
    {
        return ProductionLot::create([
            'production_dispatch_id' => $dispatchId,
            'team_id' => 1,
            'season_id' => 1,
            'lot_number' => $number,
            'kg_received' => $kg,
            'status' => 'received',
        ]);
    }

    private function dispatch(): ProductionDispatch
    {
        return ProductionDispatch::create(['team_id' => 1, 'season_id' => 1, 'kg_dispatched' => 1000, 'status' => 'received']);
    }

    private function payload(array $lots, array $overrides = []): array
    {
        return $overrides + [
            'process_number' => '45',
            'process_date' => '2026-11-19',
            'kg_exported' => 300,
            'kg_national' => 50,
            'lots' => $lots,
            'items' => [
                ['classification_type' => 'caliber', 'classification_value' => 'J', 'kg' => 300, 'boxes' => null],
            ],
        ];
    }

    public function test_one_process_over_three_lots_of_two_dispatches_is_prorated_per_lot(): void
    {
        $d1 = $this->dispatch();
        $d2 = $this->dispatch();
        $a = $this->lot($d1->id, 'A', 50);
        $b = $this->lot($d1->id, 'B', 200);
        $c = $this->lot($d2->id, 'C', 100);

        $process = $this->service->save(null, $this->payload([
            ['id' => $a->id, 'kg' => 50], ['id' => $b->id, 'kg' => 200], ['id' => $c->id, 'kg' => 100],
        ]), 1, 1);

        $this->assertEquals(350, $process->kg_to_process);
        $this->assertEquals([42.86, 171.43, 85.71], $process->items()->orderBy('production_lot_id')->pluck('kg')->map(fn($v) => (float) $v)->all());
        $this->assertEquals(300, $process->items()->sum('kg'));

        $this->assertEquals([7.14, 28.57, 14.29], $process->allocations()->orderBy('production_lot_id')->pluck('kg_national')->map(fn($v) => (float) $v)->all());

        $b->refresh();
        $this->assertSame('processed', $b->status);
        $this->assertEquals(200, $b->kg_to_process);
        $this->assertEquals(171.43, $b->items()->first()->kg);

        // Los totales de cada guía salen de sus propios lotes
        $this->assertEquals(250, $d1->fresh()->kg_received);
        $this->assertEquals(100, $d2->fresh()->kg_received);
        $this->assertSame('processed', $d2->fresh()->status);
    }

    public function test_lot_can_join_a_second_process_with_its_remaining_balance_and_results_accumulate(): void
    {
        $d = $this->dispatch();
        $lot = $this->lot($d->id, 'A', 100);

        $this->service->save(null, $this->payload([['id' => $lot->id, 'kg' => 60]], ['process_number' => '45', 'kg_exported' => 40, 'kg_national' => 20, 'items' => []]), 1, 1);
        $this->service->save(null, $this->payload([['id' => $lot->id, 'kg' => 40]], ['process_number' => '46', 'kg_exported' => 30, 'kg_national' => 10, 'items' => []]), 1, 1);

        $lot->refresh();
        $this->assertEquals(100, $lot->kg_to_process);
        $this->assertEquals(70, $lot->kg_exported);
        $this->assertEquals(30, $lot->kg_national);
        $this->assertSame(2, $lot->allocations()->count());
    }

    public function test_editing_a_process_to_drop_a_lot_returns_it_to_received(): void
    {
        $d = $this->dispatch();
        $a = $this->lot($d->id, 'A', 100);
        $b = $this->lot($d->id, 'B', 100);

        $process = $this->service->save(null, $this->payload([['id' => $a->id, 'kg' => 100], ['id' => $b->id, 'kg' => 100]]), 1, 1);
        $this->service->save($process, $this->payload([['id' => $a->id, 'kg' => 100]], ['kg_exported' => 100, 'kg_national' => 0, 'items' => []]), 1, 1);

        $this->assertSame('received', $b->fresh()->status);
        $this->assertNull($b->fresh()->kg_to_process);
        $this->assertSame('processed', $a->fresh()->status);
        $this->assertSame(1, ProductionProcess::find($process->id)->allocations()->count());
        $this->assertSame(0, DB::table('production_dispatch_items')->where('production_lot_id', $b->id)->count());
    }

    public function test_deleting_a_process_releases_its_lots(): void
    {
        $d = $this->dispatch();
        $a = $this->lot($d->id, 'A', 100);

        $process = $this->service->save(null, $this->payload([['id' => $a->id, 'kg' => 100]], ['kg_exported' => 100, 'kg_national' => 0, 'items' => []]), 1, 1);
        $this->service->delete($process);

        $this->assertSame('received', $a->fresh()->status);
        $this->assertSame('received', $d->fresh()->status);
        $this->assertNull($d->fresh()->kg_exported);
    }

    public function test_refresh_after_lots_removed_recalculates_or_removes_the_process(): void
    {
        $d = $this->dispatch();
        $a = $this->lot($d->id, 'A', 100);
        $b = $this->lot($d->id, 'B', 100);

        $process = $this->service->save(null, $this->payload([['id' => $a->id, 'kg' => 100], ['id' => $b->id, 'kg' => 100]], ['kg_exported' => 100, 'kg_national' => 100, 'items' => []]), 1, 1);

        DB::table('production_process_lots')->where('production_lot_id', $b->id)->delete();
        $this->service->refreshAfterLotsRemoved([$process->id]);
        $this->assertEquals(100, $process->fresh()->kg_to_process);
        $this->assertEquals(50, $process->fresh()->kg_exported);

        DB::table('production_process_lots')->delete();
        $this->service->refreshAfterLotsRemoved([$process->id]);
        $this->assertNull(ProductionProcess::find($process->id));
    }
}

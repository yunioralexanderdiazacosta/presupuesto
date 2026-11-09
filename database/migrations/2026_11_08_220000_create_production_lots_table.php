<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_dispatch_id')->constrained('production_dispatches')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            // Recepción en packing
            $table->string('lot_number', 50);
            $table->date('reception_date');
            $table->decimal('kg_received', 12, 2);
            $table->text('observations')->nullable();
            $table->string('status', 20)->default('received'); // received, processed
            // Proceso
            $table->date('process_date')->nullable();
            $table->decimal('kg_to_process', 12, 2)->nullable();
            $table->decimal('kg_exported', 12, 2)->nullable();
            $table->decimal('kg_national', 12, 2)->nullable();
            $table->decimal('kg_industrial', 12, 2)->nullable();
            $table->decimal('kg_waste', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['team_id', 'season_id', 'lot_number']);
        });

        Schema::table('production_dispatch_items', function (Blueprint $table) {
            $table->foreignId('production_lot_id')->nullable()->after('production_dispatch_id')
                ->constrained('production_lots')->cascadeOnDelete();
        });

        $this->backfillLots();
    }

    /**
     * Cada guía ya procesada pasa a tener un lote con sus datos de proceso.
     * Las guías pendientes que ya tenían N° de lote quedan como lote recepcionado.
     */
    private function backfillLots(): void
    {
        $now = now();

        DB::table('production_dispatches')
            ->orderBy('id')
            ->get()
            ->each(function ($d) use ($now) {
                $processed = $d->status === 'processed';
                $hasLot = !empty($d->lot_number);

                if (!$processed && !$hasLot) {
                    return;
                }

                $kgToProcess = $processed ? $d->kg_received : null;

                $lotId = DB::table('production_lots')->insertGetId([
                    'production_dispatch_id' => $d->id,
                    'team_id' => $d->team_id,
                    'season_id' => $d->season_id,
                    'lot_number' => $hasLot ? $d->lot_number : $d->guide_number,
                    'reception_date' => $d->process_date ?: $d->dispatch_date,
                    'kg_received' => $kgToProcess ?: $d->kg_dispatched,
                    'status' => $processed ? 'processed' : 'received',
                    'process_date' => $processed ? $d->process_date : null,
                    'kg_to_process' => $kgToProcess,
                    'kg_exported' => $processed ? $d->kg_exported : null,
                    'kg_national' => $processed ? $d->kg_national : null,
                    'kg_industrial' => $processed ? $d->kg_industrial : null,
                    'kg_waste' => $processed ? $d->kg_waste : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('production_dispatch_items')
                    ->where('production_dispatch_id', $d->id)
                    ->update(['production_lot_id' => $lotId]);

                if (!$processed) {
                    DB::table('production_dispatches')->where('id', $d->id)->update(['status' => 'received']);
                }
            });
    }

    public function down(): void
    {
        Schema::table('production_dispatch_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_lot_id');
        });

        DB::table('production_dispatches')->where('status', 'received')->update(['status' => 'dispatched']);

        Schema::dropIfExists('production_lots');
    }
};

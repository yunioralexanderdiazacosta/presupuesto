<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proceso del packing tal como se informa: puede reunir lotes de distintas guías
        Schema::create('production_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('process_number', 50);
            $table->date('process_date');
            $table->decimal('kg_to_process', 12, 2)->default(0);
            $table->decimal('kg_exported', 12, 2)->default(0);
            $table->decimal('kg_national', 12, 2)->default(0);
            $table->decimal('kg_industrial', 12, 2)->default(0);
            $table->decimal('kg_waste', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['team_id', 'season_id', 'process_number']);
        });

        // Parte del proceso que le corresponde a cada lote (ya prorrateada)
        Schema::create('production_process_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_process_id')->constrained('production_processes')->cascadeOnDelete();
            $table->foreignId('production_lot_id')->constrained('production_lots')->cascadeOnDelete();
            $table->decimal('kg_to_process', 12, 2)->default(0);
            $table->decimal('kg_exported', 12, 2)->default(0);
            $table->decimal('kg_national', 12, 2)->default(0);
            $table->decimal('kg_industrial', 12, 2)->default(0);
            $table->decimal('kg_waste', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['production_process_id', 'production_lot_id'], 'process_lot_unique');
        });

        Schema::table('production_dispatch_items', function (Blueprint $table) {
            $table->foreignId('production_process_id')->nullable()->after('production_lot_id')
                ->constrained('production_processes')->cascadeOnDelete();
        });

        $this->backfillProcesses();
    }

    /** Cada lote ya procesado pasa a ser un proceso de un solo lote, con los mismos valores. */
    private function backfillProcesses(): void
    {
        $now = now();

        DB::table('production_lots')
            ->where('status', 'processed')
            ->orderBy('id')
            ->get()
            ->each(function ($lot) use ($now) {
                $kg = fn($v) => (float) ($v ?? 0);

                $processId = DB::table('production_processes')->insertGetId([
                    'team_id' => $lot->team_id,
                    'season_id' => $lot->season_id,
                    'process_number' => $lot->lot_number,
                    'process_date' => $lot->process_date ?: $lot->reception_date,
                    'kg_to_process' => $kg($lot->kg_to_process),
                    'kg_exported' => $kg($lot->kg_exported),
                    'kg_national' => $kg($lot->kg_national),
                    'kg_industrial' => $kg($lot->kg_industrial),
                    'kg_waste' => $kg($lot->kg_waste),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('production_process_lots')->insert([
                    'production_process_id' => $processId,
                    'production_lot_id' => $lot->id,
                    'kg_to_process' => $kg($lot->kg_to_process),
                    'kg_exported' => $kg($lot->kg_exported),
                    'kg_national' => $kg($lot->kg_national),
                    'kg_industrial' => $kg($lot->kg_industrial),
                    'kg_waste' => $kg($lot->kg_waste),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('production_dispatch_items')
                    ->where('production_lot_id', $lot->id)
                    ->update(['production_process_id' => $processId]);
            });
    }

    public function down(): void
    {
        Schema::table('production_dispatch_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_process_id');
        });

        Schema::dropIfExists('production_process_lots');
        Schema::dropIfExists('production_processes');
    }
};

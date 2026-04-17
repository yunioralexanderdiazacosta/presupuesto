<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla pivote
        Schema::create('daily_yield_cost_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_yield_id')->constrained('daily_yields')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Migrar datos existentes: cost_center_id → pivote
        $yields = DB::table('daily_yields')
            ->whereNotNull('cost_center_id')
            ->select('id', 'cost_center_id')
            ->get();

        $inserts = $yields->map(fn($y) => [
            'daily_yield_id' => $y->id,
            'cost_center_id' => $y->cost_center_id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('daily_yield_cost_center')->insert($chunk);
        }

        // 3. Eliminar columna original
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn('cost_center_id');
        });
    }

    public function down(): void
    {
        // Re-agregar columna
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('target_price_bonus')
                ->constrained('cost_centers')->nullOnDelete();
        });

        // Restaurar primer CC de cada yield
        $pivots = DB::table('daily_yield_cost_center')
            ->select('daily_yield_id', DB::raw('MIN(cost_center_id) as cost_center_id'))
            ->groupBy('daily_yield_id')
            ->get();

        foreach ($pivots as $p) {
            DB::table('daily_yields')
                ->where('id', $p->daily_yield_id)
                ->update(['cost_center_id' => $p->cost_center_id]);
        }

        Schema::dropIfExists('daily_yield_cost_center');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_dispatch_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_dispatch_id')->constrained('production_dispatches')->cascadeOnDelete();
            $table->foreignId('cost_center_variety_id')->constrained('cost_center_varieties')->cascadeOnDelete();
            $table->decimal('kg_dispatched', 12, 2);
            $table->timestamps();

            $table->unique(['production_dispatch_id', 'cost_center_variety_id'], 'pdcc_dispatch_ccv_unique');
        });

        // Cada guía existente queda con su cuartel actual y todos sus kg
        $now = now();
        DB::table('production_dispatches')->orderBy('id')->get(['id', 'cost_center_variety_id', 'kg_dispatched'])
            ->each(fn($d) => DB::table('production_dispatch_cost_centers')->insert([
                'production_dispatch_id' => $d->id,
                'cost_center_variety_id' => $d->cost_center_variety_id,
                'kg_dispatched' => $d->kg_dispatched,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

        // La guía ya no tiene un único cuartel: la columna queda solo como dato histórico
        DB::statement('ALTER TABLE production_dispatches MODIFY cost_center_variety_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Se restaura el primer cuartel de cada guía
        DB::table('production_dispatches')->orderBy('id')->get(['id'])->each(function ($d) {
            $first = DB::table('production_dispatch_cost_centers')
                ->where('production_dispatch_id', $d->id)->orderBy('id')->value('cost_center_variety_id');
            DB::table('production_dispatches')->where('id', $d->id)->update(['cost_center_variety_id' => $first]);
        });

        DB::statement('ALTER TABLE production_dispatches MODIFY cost_center_variety_id BIGINT UNSIGNED NOT NULL');

        Schema::dropIfExists('production_dispatch_cost_centers');
    }
};

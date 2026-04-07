<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // 1. Agregar columna project_evaluation_id (nullable) a las 3 tablas
        // =====================================================================
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                ->constrained('project_evaluations')->cascadeOnDelete();
        });

        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                ->constrained('project_evaluations')->cascadeOnDelete();
        });

        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                ->constrained('project_evaluations')->cascadeOnDelete();
        });

        // =====================================================================
        // 2. Duplicar datos existentes para cada evaluación del mismo team
        // =====================================================================

        // RNP Prices
        $rnpPrices = DB::table('rnp_prices')->whereNull('project_evaluation_id')->get();
        foreach ($rnpPrices as $price) {
            $evaluations = DB::table('project_evaluations')
                ->where('team_id', $price->team_id)
                ->pluck('id');

            foreach ($evaluations as $evalId) {
                DB::table('rnp_prices')->insert([
                    'team_id'                => $price->team_id,
                    'project_evaluation_id'  => $evalId,
                    'variety_id'             => $price->variety_id,
                    'week'                   => $price->week,
                    'price_usd'              => $price->price_usd,
                    'created_at'             => $price->created_at,
                    'updated_at'             => $price->updated_at,
                ]);
            }
        }
        // Eliminar los registros globales originales (sin evaluation)
        DB::table('rnp_prices')->whereNull('project_evaluation_id')->delete();

        // Variety Cost Params
        $costParams = DB::table('variety_cost_params')->whereNull('project_evaluation_id')->get();
        foreach ($costParams as $param) {
            $evaluations = DB::table('project_evaluations')
                ->where('team_id', $param->team_id)
                ->pluck('id');

            foreach ($evaluations as $evalId) {
                DB::table('variety_cost_params')->insert([
                    'team_id'                => $param->team_id,
                    'project_evaluation_id'  => $evalId,
                    'variety_id'             => $param->variety_id,
                    'pct_embalaje'           => $param->pct_embalaje,
                    'precio_proceso'         => $param->precio_proceso,
                    'created_at'             => $param->created_at,
                    'updated_at'             => $param->updated_at,
                ]);
            }
        }
        DB::table('variety_cost_params')->whereNull('project_evaluation_id')->delete();

        // Kg Yield Costs
        $kgCosts = DB::table('kg_yield_costs')->whereNull('project_evaluation_id')->get();
        foreach ($kgCosts as $cost) {
            $evaluations = DB::table('project_evaluations')
                ->where('team_id', $cost->team_id)
                ->pluck('id');

            foreach ($evaluations as $evalId) {
                DB::table('kg_yield_costs')->insert([
                    'team_id'                => $cost->team_id,
                    'project_evaluation_id'  => $evalId,
                    'kg_ha'                  => $cost->kg_ha,
                    'cost_usd'               => $cost->cost_usd,
                    'created_at'             => $cost->created_at,
                    'updated_at'             => $cost->updated_at,
                ]);
            }
        }
        DB::table('kg_yield_costs')->whereNull('project_evaluation_id')->delete();

        // =====================================================================
        // 3. Cambiar constraints: eliminar unique viejo, crear nuevo, NOT NULL
        // =====================================================================
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'variety_id', 'week']);
            $table->unique(['project_evaluation_id', 'variety_id', 'week']);
        });

        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'variety_id']);
            $table->unique(['project_evaluation_id', 'variety_id']);
        });

        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'kg_ha']);
            $table->unique(['project_evaluation_id', 'kg_ha']);
        });

        // Hacer NOT NULL ahora que todos los registros tienen valor
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Revertir constraints
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'variety_id', 'week']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'variety_id', 'week']);
        });

        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'variety_id']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'variety_id']);
        });

        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'kg_ha']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'kg_ha']);
        });
    }
};

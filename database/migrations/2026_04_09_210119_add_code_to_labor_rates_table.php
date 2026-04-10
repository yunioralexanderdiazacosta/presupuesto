<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_rates', function (Blueprint $table) {
            $table->unsignedInteger('code')->nullable()->after('team_id');
        });

        // Asignar códigos auto-incrementales por team_id + season_id
        $combos = DB::table('labor_rates')->select('team_id', 'season_id')->distinct()->get();
        foreach ($combos as $combo) {
            $rates = DB::table('labor_rates')
                ->where('team_id', $combo->team_id)
                ->where('season_id', $combo->season_id)
                ->orderBy('id')
                ->get();
            foreach ($rates as $i => $rate) {
                DB::table('labor_rates')
                    ->where('id', $rate->id)
                    ->update(['code' => $i + 1]);
            }
        }

        Schema::table('labor_rates', function (Blueprint $table) {
            $table->unsignedInteger('code')->nullable(false)->change();
            $table->unique(['team_id', 'season_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('labor_rates', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'season_id', 'code']);
            $table->dropColumn('code');
        });
    }
};

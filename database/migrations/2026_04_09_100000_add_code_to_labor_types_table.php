<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columna nullable
        Schema::table('labor_types', function (Blueprint $table) {
            $table->unsignedInteger('code')->nullable()->after('team_id');
        });

        // 2. Asignar códigos secuenciales por equipo a registros existentes
        $teams = DB::table('labor_types')->distinct()->pluck('team_id');
        foreach ($teams as $teamId) {
            $labors = DB::table('labor_types')
                ->where('team_id', $teamId)
                ->orderBy('id')
                ->get();
            foreach ($labors as $i => $labor) {
                DB::table('labor_types')
                    ->where('id', $labor->id)
                    ->update(['code' => $i + 1]);
            }
        }

        // 3. Hacer NOT NULL y agregar unique por equipo
        Schema::table('labor_types', function (Blueprint $table) {
            $table->unsignedInteger('code')->nullable(false)->change();
            $table->unique(['team_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('labor_types', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'code']);
            $table->dropColumn('code');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tablas de líneas de presupuesto que pertenecen a una versión
    private array $lineTables = [
        'agrochemicals',
        'fertilizers',
        'supplies',
        'services',
        'harvests',
        'man_powers',
        'administrations',
        'fields',
    ];

    public function up(): void
    {
        Schema::create('budget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->text('observations')->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['season_id', 'team_id']);
        });

        foreach ($this->lineTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('budget_version_id')->nullable()->constrained('budget_versions')->cascadeOnDelete();
            });
        }

        // Cada temporada existente recibe una versión oficial "Original" con todas sus líneas actuales
        $now = now();
        foreach (DB::table('seasons')->get(['id', 'team_id']) as $season) {
            $versionId = DB::table('budget_versions')->insertGetId([
                'season_id'   => $season->id,
                'team_id'     => $season->team_id,
                'name'        => 'Original',
                'is_official' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            foreach ($this->lineTables as $tableName) {
                DB::table($tableName)
                    ->where('season_id', $season->id)
                    ->update(['budget_version_id' => $versionId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->lineTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['budget_version_id']);
                $table->dropColumn('budget_version_id');
            });
        }

        Schema::dropIfExists('budget_versions');
    }
};

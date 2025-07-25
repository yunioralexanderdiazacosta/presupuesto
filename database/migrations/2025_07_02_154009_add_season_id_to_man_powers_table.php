<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('man_powers', function (Blueprint $table) {
             $table->unsignedBigInteger('season_id')->nullable()->after('id');
        // Si quieres agregar la relación foránea:
        $table->foreign('season_id')->references('id')->on('seasons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('man_powers', function (Blueprint $table) {
             // Si agregaste la relación foránea, primero elimínala:
        // $table->dropForeign(['season_id']);
        $table->dropColumn('season_id');
        });
    }
};

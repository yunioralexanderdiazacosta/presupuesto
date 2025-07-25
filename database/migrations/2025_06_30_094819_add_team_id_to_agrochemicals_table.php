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
        Schema::table('agrochemicals', function (Blueprint $table) {
              $table->unsignedBigInteger('team_id')->nullable()->after('id');
        // Si quieres agregar la relación con la tabla teams:
         $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agrochemicals', function (Blueprint $table) {
            // Si agregaste la foreign key, primero elimínala:
        // $table->dropForeign(['team_id']);
        $table->dropColumn('team_id');
        });
    }
};

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
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('fruit_id')->nullable()->constrained('fruits')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('varieties')->cascadeOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->cascadeOnDelete();
            $table->foreignId('development_state_id')->nullable()->constrained('development_states')->cascadeOnDelete();
            $table->year('year_plantation')->nullable();
            $table->boolean('status')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign(['fruit_id']);
            $table->dropColumn('fruit_id');
            $table->dropForeign(['variety_id']);
            $table->dropColumn('variety_id');
            $table->dropForeign(['parcel_id']);
            $table->dropColumn('parcel_id');
            $table->dropForeign(['development_state_id']);
            $table->dropColumn('development_state_id');
            $table->dropColumn('year_plantation');
            $table->dropColumn('status');            
        });
    }
};

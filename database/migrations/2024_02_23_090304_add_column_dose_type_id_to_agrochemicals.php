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
            $table->dropColumn('dose_type');
            $table->foreignId('dose_type_id')->after('unit_id')->nullable()->constrained('dose_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agrochemicals', function (Blueprint $table) {
            $table->dropColumn('dose_type_id');
            $table->string('dose_type');
        });
    }
};

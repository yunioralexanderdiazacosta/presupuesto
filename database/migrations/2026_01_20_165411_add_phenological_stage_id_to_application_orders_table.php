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
        Schema::table('application_orders', function (Blueprint $table) {
            $table->foreignId('phenological_stage_id')->nullable()->constrained('phenological_stages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_orders', function (Blueprint $table) {
            $table->dropForeign(['phenological_stage_id']);
            $table->dropColumn('phenological_stage_id');
        });
    }
};

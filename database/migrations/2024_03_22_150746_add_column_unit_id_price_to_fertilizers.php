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
        Schema::table('fertilizers', function (Blueprint $table) {
            $table->foreignId('unit_id_price')->nullable()->constrained('units')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fertilizers', function (Blueprint $table) {
            $table->dropForeign(['unit_id_price']);
            $table->dropColumn('unit_id_price');
        });
    }
};

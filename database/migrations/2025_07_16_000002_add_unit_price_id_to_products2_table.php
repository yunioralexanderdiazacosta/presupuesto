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
        Schema::table('products2', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products2', function (Blueprint $table) {
            $table->dropColumn('unit_price_id');
        });
    }
};

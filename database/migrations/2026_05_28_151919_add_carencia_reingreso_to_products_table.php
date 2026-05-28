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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('carencia')->nullable()->after('active_ingredient')->comment('Días de carencia del producto');
            $table->integer('reingreso')->nullable()->after('carencia')->comment('Horas de reingreso del producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['carencia', 'reingreso']);
        });
    }
};

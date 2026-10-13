<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_order_product', function (Blueprint $table) {
            $table->decimal('dosis_por_100', 12, 4)->nullable()->change();
            $table->decimal('dosis_por_hectarea', 12, 4)->nullable()->change();
            $table->decimal('cantidad_por_hectarea', 12, 4)->change();
            $table->decimal('cantidad_total', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('application_order_product', function (Blueprint $table) {
            $table->decimal('dosis_por_100', 10, 2)->nullable()->change();
            $table->decimal('dosis_por_hectarea', 10, 2)->nullable()->change();
            $table->decimal('cantidad_por_hectarea', 10, 2)->change();
            $table->decimal('cantidad_total', 10, 2)->change();
        });
    }
};

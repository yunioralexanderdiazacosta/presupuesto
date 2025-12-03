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
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Hacer fuel_type nullable ya que se está usando product_id en su lugar
            if (Schema::hasColumn('fuel_outflows', 'fuel_type')) {
                $table->string('fuel_type')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_outflows', 'fuel_type')) {
                $table->string('fuel_type')->nullable(false)->change();
            }
        });
    }
};

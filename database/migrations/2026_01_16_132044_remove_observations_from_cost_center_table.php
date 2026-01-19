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
        Schema::table('application_order_cost_center', function (Blueprint $table) {
            $table->dropColumn('observations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_order_cost_center', function (Blueprint $table) {
            $table->text('observations')->nullable()->comment('Observaciones específicas por centro de costo');
        });
    }
};

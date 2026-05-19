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
        Schema::table('terminations', function (Blueprint $table) {
            $table->decimal('vacation_days', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('terminations', function (Blueprint $table) {
            $table->integer('vacation_days')->nullable()->change();
        });
    }
};

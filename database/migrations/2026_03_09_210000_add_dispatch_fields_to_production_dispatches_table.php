<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_dispatches', function (Blueprint $table) {
            $table->string('driver')->nullable()->after('carrier');
            $table->string('license_plate')->nullable()->after('driver');
            $table->string('bin_type')->nullable()->after('bins_dispatched');
            $table->string('box_type')->nullable()->after('bin_type');
            $table->integer('bins_quantity')->nullable()->after('box_type');
            $table->integer('boxes_quantity')->nullable()->after('bins_quantity');
            $table->string('status')->default('dispatched')->after('observations'); // dispatched, processed
        });
    }

    public function down(): void
    {
        Schema::table('production_dispatches', function (Blueprint $table) {
            $table->dropColumn(['driver', 'license_plate', 'bin_type', 'box_type', 'bins_quantity', 'boxes_quantity', 'status']);
        });
    }
};

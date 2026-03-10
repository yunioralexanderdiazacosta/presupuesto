<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('box_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Alter production_dispatches: replace string columns with FKs
        Schema::table('production_dispatches', function (Blueprint $table) {
            $table->foreignId('bin_type_id')->nullable()->after('kg_dispatched')->constrained('bin_types')->nullOnDelete();
            $table->foreignId('box_type_id')->nullable()->after('bins_quantity')->constrained('box_types')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->after('boxes_quantity')->constrained('carriers')->nullOnDelete();
            $table->dropColumn(['bin_type', 'box_type', 'carrier']);
        });
    }

    public function down(): void
    {
        Schema::table('production_dispatches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bin_type_id');
            $table->dropConstrainedForeignId('box_type_id');
            $table->dropConstrainedForeignId('carrier_id');
            $table->string('bin_type', 50)->nullable();
            $table->string('box_type', 50)->nullable();
            $table->string('carrier', 100)->nullable();
        });

        Schema::dropIfExists('carriers');
        Schema::dropIfExists('box_types');
        Schema::dropIfExists('bin_types');
    }
};

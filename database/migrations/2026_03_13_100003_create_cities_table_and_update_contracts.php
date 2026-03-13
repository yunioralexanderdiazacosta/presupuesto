<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('city');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('health_plan_id')->constrained('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('health_plan_id');
        });

        Schema::dropIfExists('cities');
    }
};

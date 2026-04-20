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
        Schema::table('machineries', function (Blueprint $table) {
            if (!Schema::hasColumn('machineries', 'modelo')) {
                $table->string('modelo')->nullable()->after('brand');
            }

            if (!Schema::hasColumn('machineries', 'patente')) {
                $table->string('patente')->nullable()->after('modelo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            if (Schema::hasColumn('machineries', 'patente')) {
                $table->dropColumn('patente');
            }

            if (Schema::hasColumn('machineries', 'modelo')) {
                $table->dropColumn('modelo');
            }
        });
    }
};

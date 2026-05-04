<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_orders', function (Blueprint $table) {
            $table->decimal('superficie_total', 10, 2)->nullable()->after('mojamiento');
        });
    }

    public function down(): void
    {
        Schema::table('application_orders', function (Blueprint $table) {
            $table->dropColumn('superficie_total');
        });
    }
};

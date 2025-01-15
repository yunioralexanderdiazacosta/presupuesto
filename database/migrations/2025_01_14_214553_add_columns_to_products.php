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
            $table->foreignId('level1_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('level2_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('level3_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('level4_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['level1_id']);
            $table->dropColumn('level1_id');
            $table->dropForeign(['level2_id']);
            $table->dropColumn('level2_id');
            $table->dropForeign(['level3_id']);
            $table->dropColumn('level3_id');
            $table->dropForeign(['level4_id']);
            $table->dropColumn('level4_id');
        });
    }
};

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
        Schema::table('harvests', function (Blueprint $table) {
             $table->dropForeign(['subfamily_id']);
            $table->foreignId('subfamily_id')
            ->change()
            ->constrained('level3s')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
             $table->foreignId('subfamily_id')
           ->change()
           ->constrained()
           ->onDelete('cascade');
        });
    }
};

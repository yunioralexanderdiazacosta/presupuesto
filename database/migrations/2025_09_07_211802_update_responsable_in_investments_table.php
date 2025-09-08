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
        Schema::table('investments', function (Blueprint $table) {
            $table->string('responsable')->after('name');
            $table->dropForeign(['responsable_id']);
            $table->dropColumn('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->foreign('responsable_id')->references('id')->on('users')->nullOnDelete();
            $table->dropColumn('responsable');
        });
    }
};

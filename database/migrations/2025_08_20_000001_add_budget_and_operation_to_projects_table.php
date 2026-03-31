<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('budget')->nullable()->after('observations');
            $table->foreignId('operation_id')->nullable()->after('budget')->constrained('operations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['operation_id']);
            $table->dropColumn(['budget', 'operation_id']);
        });
    }
};

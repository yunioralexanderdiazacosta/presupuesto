<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fertilizers', function (Blueprint $table) {
            $table->foreignId('operation_id')->nullable()->after('subfamily_id')->constrained('operations')->nullOnDelete();
        });

        // Backfill registros existentes con la operación "Gasto" (si existe en el catálogo)
        $defaultOperationId = \App\Models\Operation::whereRaw('LOWER(name) LIKE ?', ['%gasto%'])->value('id');
        if ($defaultOperationId) {
            \Illuminate\Support\Facades\DB::table('fertilizers')
                ->whereNull('operation_id')
                ->update(['operation_id' => $defaultOperationId]);
        }
    }

    public function down(): void
    {
        Schema::table('fertilizers', function (Blueprint $table) {
            $table->dropForeign(['operation_id']);
            $table->dropColumn('operation_id');
        });
    }
};

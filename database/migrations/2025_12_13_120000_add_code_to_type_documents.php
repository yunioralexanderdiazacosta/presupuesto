<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_documents', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name');
        });

        // Actualizar registros existentes con códigos SII Chile
        DB::table('type_documents')->where('name', 'FACTURA')->update(['code' => '33']);
        DB::table('type_documents')->where('name', 'BOLETA')->update(['code' => '39']);
        DB::table('type_documents')->where('name', 'NOTA CREDITO')->update(['code' => '61']);
        DB::table('type_documents')->where('name', 'NOTA DEBITO')->update(['code' => '56']);
    }

    public function down(): void
    {
        Schema::table('type_documents', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};

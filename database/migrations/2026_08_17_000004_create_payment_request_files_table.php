<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        // Migrar el archivo único existente (si lo hay) a la nueva tabla
        DB::table('payment_requests')->whereNotNull('file_path')->get()->each(function ($row) {
            DB::table('payment_request_files')->insert([
                'payment_request_id' => $row->id,
                'file_path' => $row->file_path,
                'original_name' => basename($row->file_path),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('file_path')->nullable();
        });

        DB::table('payment_request_files')->get()->each(function ($row) {
            DB::table('payment_requests')->where('id', $row->payment_request_id)->update([
                'file_path' => $row->file_path,
            ]);
        });

        Schema::dropIfExists('payment_request_files');
    }
};

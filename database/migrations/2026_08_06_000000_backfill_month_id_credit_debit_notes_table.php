<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Backfill: month_id nunca se completaba al crear/editar notas de crédito/débito
// (ver CreditDebitNote::booted()), así que todos los registros existentes lo tienen NULL.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE credit_debit_notes SET month_id = MONTH(date) WHERE month_id IS NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE credit_debit_notes SET month_id = NULL');
    }
};

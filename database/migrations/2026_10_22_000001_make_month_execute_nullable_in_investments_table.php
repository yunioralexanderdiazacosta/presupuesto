<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // El formulario ya no envía mes; la columna debe aceptar NULL (sin doctrine/dbal, se usa SQL directo)
    public function up(): void
    {
        DB::statement('ALTER TABLE investments MODIFY month_execute TINYINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE investments SET month_execute = 1 WHERE month_execute IS NULL');
        DB::statement('ALTER TABLE investments MODIFY month_execute TINYINT UNSIGNED NOT NULL');
    }
};

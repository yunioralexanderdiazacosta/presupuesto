<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('consumptions', function (Blueprint $table) {
            // Eliminar la foreign key existente
            $table->dropForeign(['machinary_id']);
            // Renombrar la columna
            $table->renameColumn('machinary_id', 'machinery_id');
        });
        // Volver a crear la foreign key
        Schema::table('consumptions', function (Blueprint $table) {
            $table->foreign('machinery_id')->references('id')->on('machineries');
        });
    }

    public function down()
    {
        Schema::table('consumptions', function (Blueprint $table) {
            $table->dropForeign(['machinery_id']);
            $table->renameColumn('machinery_id', 'machinary_id');
        });
        Schema::table('consumptions', function (Blueprint $table) {
            $table->foreign('machinary_id')->references('id')->on('machineries');
        });
    }
};

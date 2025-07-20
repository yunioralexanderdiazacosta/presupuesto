<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Elimina la columna estimate_name de la tabla estimates.
     */
    public function up()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('estimate_name');
        });
    }

    /**
     * Vuelve a agregar la columna estimate_name a la tabla estimates.
     */
    public function down()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('estimate_name')->nullable();
        });
    }
};

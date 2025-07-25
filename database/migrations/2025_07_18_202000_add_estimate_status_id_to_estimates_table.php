<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Agrega la columna estimate_status_id a la tabla estimates y crea la relación foránea.
     */
    public function up()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->unsignedBigInteger('estimate_status_id')->nullable()->after('id');
            $table->foreign('estimate_status_id')->references('id')->on('estimate_status')->onDelete('set null');
        });
    }

    /**
     * Elimina la columna estimate_status_id y la relación foránea de la tabla estimates.
     */
    public function down()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['estimate_status_id']);
            $table->dropColumn('estimate_status_id');
        });
    }
};

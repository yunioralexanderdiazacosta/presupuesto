<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('products2', function (Blueprint $table) {
            $table->string('form')->nullable()->after('unit_price_id');
        });
    }

    public function down()
    {
        Schema::table('products2', function (Blueprint $table) {
            $table->dropColumn('form');
        });
    }
};

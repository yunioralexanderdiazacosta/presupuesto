<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            //
            $table->string('product_name');
            $table->decimal('quantity');
            $table->integer('price');
            $table->text('observations')->nullable();
            $table->foreignId('subfamily_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id_price')->constrained()->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            //
            $table->dropColumn('product_name');
            $table->dropColumn('quantity');
            $table->dropColumn	('price');
            $table->dropColumn('observations')->nullable();
            $table->dropForeign('subfamily_id')->constrained()->cascadeOnDelete();
            $table->dropForeign('unit_id')->constrained()->cascadeOnDelete();
            $table->dropForeign('unit_id_price')->constrained()->cascadeOnDelete();
        });
    }
};

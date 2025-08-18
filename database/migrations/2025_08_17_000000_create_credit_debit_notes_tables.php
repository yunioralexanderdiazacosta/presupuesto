<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::create('credit_debit_notes', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('team_id');
			$table->unsignedBigInteger('season_id');
			$table->enum('type', ['credito', 'debito']);
			$table->unsignedBigInteger('invoice_id');
			$table->unsignedBigInteger('supplier_id');
			$table->string('number');
			$table->date('date');
			$table->text('reason')->nullable();
			$table->boolean('affects_inventory')->default(false);
			$table->unsignedBigInteger('user_id');
			$table->timestamps();
			// Foreign keys (ajusta los nombres de tablas si es necesario)
			$table->foreign('team_id')->references('id')->on('teams');
			$table->foreign('season_id')->references('id')->on('seasons');
			$table->foreign('invoice_id')->references('id')->on('invoices');
			$table->foreign('supplier_id')->references('id')->on('suppliers');
			$table->foreign('user_id')->references('id')->on('users');
		});

		Schema::create('credit_debit_note_items', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('credit_debit_note_id');
			$table->unsignedBigInteger('product_id');
			$table->unsignedBigInteger('unit_id');
			$table->decimal('quantity', 12, 2);
			$table->decimal('unit_price', 12, 2);
			$table->timestamps();
			// Foreign keys
			$table->foreign('credit_debit_note_id')->references('id')->on('credit_debit_notes')->onDelete('cascade');
			$table->foreign('product_id')->references('id')->on('products');
			$table->foreign('unit_id')->references('id')->on('units');
		});
	}

	public function down()
	{
		Schema::dropIfExists('credit_debit_note_items');
		Schema::dropIfExists('credit_debit_notes');
	}
};

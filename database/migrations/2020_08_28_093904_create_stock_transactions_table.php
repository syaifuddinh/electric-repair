<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStockTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stock_transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id')->unsigned();
			$table->integer('rack_id')->unsigned()->nullable();
			$table->integer('item_id')->unsigned();
			$table->integer('type_transaction_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_transaction');
			$table->string('description', 191)->nullable();
			$table->float('qty_masuk', 10, 0)->default(0);
			$table->float('qty_keluar', 10, 0)->default(0);
			$table->float('harga_masuk', 10, 0)->default(0);
			$table->float('harga_keluar', 10, 0)->default(0);
			$table->integer('jumlah_stok')->default(0);
			$table->timestamps();
			$table->string('no_surat_jalan', 100)->nullable();
			$table->integer('customer_id')->unsigned()->nullable();
			$table->integer('warehouse_receipt_detail_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stock_transactions');
	}

}

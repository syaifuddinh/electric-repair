<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePalletUsagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pallet_usages', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('shipping_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('using_date');
			$table->date('shipping_date')->nullable();
			$table->integer('status')->default(1);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pallet_usages');
	}

}

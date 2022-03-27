<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaxesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('taxes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191);
			$table->string('name', 191);
			$table->integer('non_npwp');
			$table->integer('npwp');
			$table->integer('pemotong_pemungut');
			$table->integer('akun_pembelian')->unsigned();
			$table->integer('akun_penjualan')->unsigned();
			$table->timestamps();
			$table->integer('is_default')->unsigned()->default(0)->index();
			$table->integer('is_ppn')->unsigned()->default(0)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('taxes');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccountDefaultsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('account_defaults', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('biaya_klaim')->unsigned()->nullable();
			$table->integer('cek_giro_keluar')->unsigned()->nullable();
			$table->integer('cek_giro_masuk')->unsigned()->nullable();
			$table->integer('diskon_penjualan')->unsigned()->nullable();
			$table->integer('hutang')->unsigned()->nullable();
			$table->integer('hutang_klaim')->unsigned()->nullable();
			$table->integer('laba_bulan_berjalan')->unsigned()->nullable();
			$table->integer('laba_ditahan')->unsigned()->nullable();
			$table->integer('laba_tahun_berjalan')->unsigned()->nullable();
			$table->integer('lebih_bayar_hutang')->unsigned()->nullable();
			$table->integer('lebih_bayar_piutang')->unsigned()->nullable();
			$table->integer('pendapatan_klaim')->unsigned()->nullable();
			$table->integer('penjualan')->unsigned()->nullable();
			$table->integer('piutang')->unsigned()->nullable();
			$table->integer('ppn_in')->unsigned()->nullable();
			$table->integer('ppn_out')->unsigned()->nullable();
			$table->integer('saldo_awal')->unsigned()->nullable();
			$table->timestamps();
			$table->integer('account_kasbon_id')->unsigned()->nullable();
			$table->integer('inventory')->unsigned()->nullable();
			$table->integer('perawatan')->unsigned()->nullable();
			$table->integer('bukti_potong')->unsigned()->nullable();
			$table->integer('bukti_potong_hutang')->unsigned()->nullable();
			$table->integer('pendapatan_hibah')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('account_defaults');
	}

}

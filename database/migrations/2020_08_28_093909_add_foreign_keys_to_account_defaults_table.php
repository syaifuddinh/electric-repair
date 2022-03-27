<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccountDefaultsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('account_defaults', function(Blueprint $table)
		{
			$table->foreign('account_kasbon_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('biaya_klaim')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('bukti_potong')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('bukti_potong_hutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cek_giro_keluar')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cek_giro_masuk')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('diskon_penjualan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('hutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('hutang_klaim')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('inventory')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('laba_bulan_berjalan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('laba_ditahan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('laba_tahun_berjalan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lebih_bayar_hutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lebih_bayar_piutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('pendapatan_hibah')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('pendapatan_klaim')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('penjualan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('perawatan')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('ppn_in')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('ppn_out')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('saldo_awal')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('account_defaults', function(Blueprint $table)
		{
			$table->dropForeign('account_defaults_account_kasbon_id_foreign');
			$table->dropForeign('account_defaults_biaya_klaim_foreign');
			$table->dropForeign('account_defaults_bukti_potong_foreign');
			$table->dropForeign('account_defaults_bukti_potong_hutang_foreign');
			$table->dropForeign('account_defaults_cek_giro_keluar_foreign');
			$table->dropForeign('account_defaults_cek_giro_masuk_foreign');
			$table->dropForeign('account_defaults_diskon_penjualan_foreign');
			$table->dropForeign('account_defaults_hutang_foreign');
			$table->dropForeign('account_defaults_hutang_klaim_foreign');
			$table->dropForeign('account_defaults_inventory_foreign');
			$table->dropForeign('account_defaults_laba_bulan_berjalan_foreign');
			$table->dropForeign('account_defaults_laba_ditahan_foreign');
			$table->dropForeign('account_defaults_laba_tahun_berjalan_foreign');
			$table->dropForeign('account_defaults_lebih_bayar_hutang_foreign');
			$table->dropForeign('account_defaults_lebih_bayar_piutang_foreign');
			$table->dropForeign('account_defaults_pendapatan_hibah_foreign');
			$table->dropForeign('account_defaults_pendapatan_klaim_foreign');
			$table->dropForeign('account_defaults_penjualan_foreign');
			$table->dropForeign('account_defaults_perawatan_foreign');
			$table->dropForeign('account_defaults_piutang_foreign');
			$table->dropForeign('account_defaults_ppn_in_foreign');
			$table->dropForeign('account_defaults_ppn_out_foreign');
			$table->dropForeign('account_defaults_saldo_awal_foreign');
		});
	}

}

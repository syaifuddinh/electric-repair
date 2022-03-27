<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAssetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('assets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('asset_group_id')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->integer('asset_type');
			$table->date('date_transaction');
			$table->date('date_purchase');
			$table->float('purchase_price', 20)->default(0.00);
			$table->float('residu_price', 20)->default(0.00);
			$table->text('description', 65535)->nullable();
			$table->float('umur_ekonomis', 20)->default(0.00);
			$table->integer('method')->default(1);
			$table->integer('account_asset_id')->unsigned();
			$table->integer('account_accumulation_id')->unsigned();
			$table->integer('account_depreciation_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('vehicle_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->boolean('is_afkir')->default(0);
			$table->boolean('is_revaluasi')->default(0);
			$table->boolean('is_saldo')->default(0);
			$table->integer('status')->default(1);
			$table->float('beban_tahun', 20)->default(0.00);
			$table->float('beban_bulan', 20)->default(0.00);
			$table->float('beban_akumulasi', 20)->default(0.00);
			$table->float('nilai_buku', 20)->default(0.00);
			$table->timestamps();
			$table->date('terhitung_tanggal')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('assets');
	}

}

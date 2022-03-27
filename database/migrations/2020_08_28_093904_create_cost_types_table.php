<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCostTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cost_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('akun_biaya')->unsigned()->nullable();
			$table->integer('akun_uang_muka')->unsigned()->nullable();
			$table->integer('akun_kas_hutang')->unsigned()->nullable();
			$table->integer('cash_category_id')->unsigned()->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->string('code', 191)->unique();
			$table->string('name', 191);
			$table->integer('type');
			$table->float('initial_cost', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->boolean('is_bbm')->default(0);
			$table->boolean('is_operasional')->default(0);
			$table->boolean('is_invoice')->default(0);
			$table->boolean('is_biaya_lain')->default(0);
			$table->timestamps();
			$table->boolean('is_ppn')->nullable();
			$table->float('ppn_cost', 20)->nullable()->default(0.00);
			$table->float('qty', 20)->nullable()->default(0.00);
			$table->float('cost', 20)->nullable()->default(0.00);
			$table->integer('company_id')->unsigned()->nullable();
			$table->boolean('is_overtime')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cost_types');
	}

}

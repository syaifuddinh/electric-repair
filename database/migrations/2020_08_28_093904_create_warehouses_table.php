<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehousesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('city_id')->unsigned();
			$table->integer('warehouse_type_id')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('address', 191)->nullable();
			$table->string('latitude', 191)->nullable();
			$table->string('longitude', 191)->nullable();
			$table->integer('capacity')->default(0);
			$table->timestamps();
			$table->float('capacity_volume', 10, 0)->nullable()->default(0);
			$table->float('capacity_tonase', 10, 0)->nullable()->default(0);
			$table->integer('is_active')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouses');
	}

}

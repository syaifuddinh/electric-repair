<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRacksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('racks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id')->unsigned();
			$table->string('code', 191);
			$table->float('capacity_volume', 10, 0)->default(0);
			$table->float('capacity_volume_used', 10, 0)->default(0);
			$table->float('capacity_tonase', 10, 0)->default(0);
			$table->float('capacity_tonase_used', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->integer('storage_type_id')->unsigned()->nullable();
			$table->string('barcode', 191)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('racks');
	}

}

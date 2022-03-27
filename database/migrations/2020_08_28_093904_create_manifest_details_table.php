<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManifestDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('manifest_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('job_order_detail_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('update_by')->unsigned();
			$table->float('transported', 10, 0)->default(0);
			$table->float('leftover', 10, 0)->default(0);
			$table->timestamps();
			$table->boolean('is_invoice')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('manifest_details');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_order_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('price_list_id')->unsigned()->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('quotation_id')->unsigned()->nullable();
			$table->integer('quotation_detail_id')->unsigned()->nullable();
			$table->integer('commodity_id')->unsigned()->nullable();
			$table->integer('sender_id')->unsigned()->nullable();
			$table->integer('receiver_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->boolean('is_contract')->default(0);
			$table->float('price', 10, 0)->default(0);
			$table->float('total_price', 10, 0)->default(0);
			$table->integer('imposition')->default(1);
			$table->float('qty', 10, 0)->default(0);
			$table->float('weight', 10, 0)->default(0);
			$table->float('volume', 10, 0)->default(0);
			$table->float('long', 10, 0)->default(0);
			$table->float('wide', 10, 0)->default(0);
			$table->float('high', 10, 0)->default(0);
			$table->float('transported', 10, 0)->default(0);
			$table->float('leftover', 10, 0)->default(0);
			$table->string('item_name', 191);
			$table->string('barcode', 200);
			$table->string('no_reff', 191)->nullable();
			$table->string('no_manifest', 191)->nullable();
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->integer('manifest_id')->unsigned()->nullable();
			$table->integer('warehouse_receipt_id')->unsigned()->nullable();
			$table->date('date_out')->nullable();
			$table->integer('item_id')->unsigned()->nullable();
			$table->string('no_surat_jalan', 100)->nullable();
			$table->integer('rack_id')->nullable();
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
		Schema::drop('job_order_details');
	}

}

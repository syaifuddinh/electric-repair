<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('items', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('category_id')->unsigned()->nullable();
			$table->integer('tire_type_id')->unsigned()->nullable();
			$table->integer('tire_size_id')->unsigned()->nullable();
			$table->integer('tire_manufacture_id')->unsigned()->nullable();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('part_number', 191)->nullable();
			$table->string('barcode', 191)->nullable();
			$table->string('qrcode', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->string('image', 191)->nullable();
			$table->float('initial_cost', 10, 0)->default(0);
			$table->float('harga_beli', 10, 0)->default(0);
			$table->float('harga_jual', 10, 0)->default(0);
			$table->timestamps();
			$table->integer('account_id')->unsigned()->nullable();
			$table->integer('account_purchase_id')->unsigned()->nullable();
			$table->integer('account_purchase_retur_id')->unsigned()->nullable();
			$table->integer('account_sale_id')->unsigned()->nullable();
			$table->integer('account_sale_retur_id')->unsigned()->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('main_supplier_id')->unsigned()->nullable();
			$table->float('minimal_stock', 20)->default(0.00);
			$table->float('std_purchase', 20)->default(0.00);
			$table->float('std_sale', 20)->default(0.00);
			$table->boolean('is_stock')->default(1);
			$table->boolean('is_active')->default(1);
			$table->boolean('is_generate')->default(0);
			$table->integer('last_number')->default(0);
			$table->integer('is_package')->default(0);
			$table->integer('customer_id')->nullable();
			$table->float('long', 10, 0)->default(0);
			$table->float('wide', 10, 0)->default(0);
			$table->float('height', 10, 0)->default(0);
			$table->float('volume', 10, 0)->default(0);
			$table->integer('sender_id')->nullable();
			$table->integer('receiver_id')->nullable();
			$table->date('inbound_date')->nullable();
			$table->integer('tonase')->default(0);
			$table->integer('account_payable_id')->unsigned()->nullable();
			$table->integer('account_cash_id')->unsigned()->nullable();
			$table->integer('is_accrual')->default(1);
			$table->integer('item_type')->default(1);
			$table->integer('is_service')->default(0);
			$table->integer('is_expired')->default(0);
			$table->integer('is_bbm')->default(0);
			$table->integer('is_operational')->default(0);
			$table->integer('is_invoice')->default(0);
			$table->integer('is_overtime')->default(0);
			$table->integer('is_ppn')->default(0);
			$table->string('vendor_path_no', 191)->nullable();
			$table->string('brand_name', 191)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('items');
	}

}

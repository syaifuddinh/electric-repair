<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('items', function(Blueprint $table)
		{
			$table->foreign('account_cash_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('account_payable_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_purchase_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_purchase_retur_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_sale_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_sale_retur_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('category_id')->references('id')->on('categories')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('main_supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('tire_manufacture_id')->references('id')->on('tire_manufacturers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('tire_size_id')->references('id')->on('tire_sizes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('tire_type_id')->references('id')->on('tire_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('items', function(Blueprint $table)
		{
			$table->dropForeign('items_account_cash_id_foreign');
			$table->dropForeign('items_account_id_foreign');
			$table->dropForeign('items_account_payable_id_foreign');
			$table->dropForeign('items_account_purchase_id_foreign');
			$table->dropForeign('items_account_purchase_retur_id_foreign');
			$table->dropForeign('items_account_sale_id_foreign');
			$table->dropForeign('items_account_sale_retur_id_foreign');
			$table->dropForeign('items_category_id_foreign');
			$table->dropForeign('items_main_supplier_id_foreign');
			$table->dropForeign('items_piece_id_foreign');
			$table->dropForeign('items_tire_manufacture_id_foreign');
			$table->dropForeign('items_tire_size_id_foreign');
			$table->dropForeign('items_tire_type_id_foreign');
		});
	}

}

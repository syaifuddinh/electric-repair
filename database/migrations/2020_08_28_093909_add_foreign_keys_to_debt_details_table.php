<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDebtDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('debt_details', function(Blueprint $table)
		{
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('debts')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('nota_credit_id')->references('id')->on('nota_credits')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('nota_debet_id')->references('id')->on('nota_debets')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('payable_detail_id')->references('id')->on('payable_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('payable_id')->references('id')->on('payables')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('type_transaction_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('um_supplier_id')->references('id')->on('um_suppliers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('debt_details', function(Blueprint $table)
		{
			$table->dropForeign('debt_details_create_by_foreign');
			$table->dropForeign('debt_details_header_id_foreign');
			$table->dropForeign('debt_details_nota_credit_id_foreign');
			$table->dropForeign('debt_details_nota_debet_id_foreign');
			$table->dropForeign('debt_details_payable_detail_id_foreign');
			$table->dropForeign('debt_details_payable_id_foreign');
			$table->dropForeign('debt_details_type_transaction_id_foreign');
			$table->dropForeign('debt_details_um_supplier_id_foreign');
		});
	}

}

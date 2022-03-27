<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCostTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cost_types', function(Blueprint $table)
		{
			$table->foreign('akun_biaya')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('akun_kas_hutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('akun_uang_muka')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cash_category_id')->references('id')->on('cash_categories')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('parent_id')->references('id')->on('cost_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cost_types', function(Blueprint $table)
		{
			$table->dropForeign('cost_types_akun_biaya_foreign');
			$table->dropForeign('cost_types_akun_kas_hutang_foreign');
			$table->dropForeign('cost_types_akun_uang_muka_foreign');
			$table->dropForeign('cost_types_cash_category_id_foreign');
			$table->dropForeign('cost_types_company_id_foreign');
			$table->dropForeign('cost_types_parent_id_foreign');
			$table->dropForeign('cost_types_vendor_id_foreign');
		});
	}

}

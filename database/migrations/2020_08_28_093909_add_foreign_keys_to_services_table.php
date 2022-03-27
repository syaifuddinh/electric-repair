<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToServicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('services', function(Blueprint $table)
		{
			$table->foreign('service_type_id', 'service_type_for_id')->references('id')->on('service_types')->onUpdate('CASCADE')->onDelete('RESTRICT');
			$table->foreign('account_sale_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_group_id')->references('id')->on('service_groups')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('services', function(Blueprint $table)
		{
			$table->dropForeign('service_type_for_id');
			$table->dropForeign('services_account_sale_id_foreign');
			$table->dropForeign('services_service_group_id_foreign');
		});
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToGroupRolesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('group_roles', function(Blueprint $table)
		{
			$table->foreign('group_type_id')->references('id')->on('group_types')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('role_id')->references('id')->on('roles')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('group_roles', function(Blueprint $table)
		{
			$table->dropForeign('group_roles_group_type_id_foreign');
			$table->dropForeign('group_roles_role_id_foreign');
		});
	}

}

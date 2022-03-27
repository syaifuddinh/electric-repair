<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAssetGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('asset_groups', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191)->nullable();
			$table->string('name', 191);
			$table->float('umur_ekonomis', 20)->default(0.00);
			$table->text('description', 65535)->nullable();
			$table->integer('method')->default(1);
			$table->integer('account_asset_id')->unsigned();
			$table->integer('account_accumulation_id')->unsigned();
			$table->integer('account_depreciation_id')->unsigned();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('asset_groups');
	}

}

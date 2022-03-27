<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToManifestDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('manifest_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('manifests')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('manifest_details', function(Blueprint $table)
		{
			$table->dropForeign('manifest_details_header_id_foreign');
		});
	}

}

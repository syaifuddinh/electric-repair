<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColToStorageType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('storage_types', function (Blueprint $table) {
          $table->integer('is_stripping_area')->default(0);
        });
        DB::table('storage_types')->insert([
          'name' => 'Stripping Area',
          'is_stripping_area' => 1
        ]);
        DB::update("UPDATE storage_types SET name = 'Staging Area' WHERE is_picking_area = 1");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('storage_types', function (Blueprint $table) {
          $table->dropColumn(['is_stripping_area']);
        });
    }
}

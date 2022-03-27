<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RackIdInItemMigrationDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_migration_details', function (Blueprint $table) {
            $table->unsignedInteger('rack_id')->nullable(true)->index();
            $table->unsignedInteger('destination_rack_id')->nullable(true)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_migration_details', function (Blueprint $table) {
            $table->dropColumn(['rack_id', 'destination_rack_id']);
        });
    }
}

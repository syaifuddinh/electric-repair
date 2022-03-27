<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DestinationWarehouseInItemMigrationDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_migration_details', function (Blueprint $table) {
            $table->unsignedInteger('destination_warehouse_id')->nullable(true)->index();
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');

            $table->unsignedInteger('destination_rack_id')->nullable(true)->change();
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
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id']);

            $table->unsignedInteger('destination_rack_id')->nullable(false)->change();
        });
    }
}

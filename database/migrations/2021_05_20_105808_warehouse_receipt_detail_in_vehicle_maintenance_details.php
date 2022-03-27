<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WarehouseReceiptDetailInVehicleMaintenanceDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_maintenance_details', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true)->index();
            $table->unsignedInteger('rack_id')->nullable(true)->index();

            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('restrict');
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_maintenance_details', function (Blueprint $table) {
            $table->dropForeign(['warehouse_receipt_detail_id']);
            $table->dropColumn(['warehouse_receipt_detail_id']);

            $table->dropForeign(['rack_id']);
            $table->dropColumn(['rack_id']);
        });
    }
}

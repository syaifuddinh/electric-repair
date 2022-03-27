<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UomInWarehouseReceiptDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->integer('qty_2')->nullable(false)->default(0);
            $table->unsignedInteger('piece_id_2')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->dropColumn(['qty_2', 'piece_id_2']);
        });
    }
}

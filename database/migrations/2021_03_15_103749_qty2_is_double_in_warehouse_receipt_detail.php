<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Qty2IsDoubleInWarehouseReceiptDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->dropColumn(['qty_2']);
        });
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->double('qty_2', 0, 3)->nullable(false)->default(0);
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
            $table->dropColumn(['qty_2']);
        });
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->integer('qty_2')->nullable(false)->default(0);
        });
    }
}

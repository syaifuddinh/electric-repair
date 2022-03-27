<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReceiptTypeInWarehouseReceipt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')->nullable(true)->change();
            $table->string('receiver', 100)->nullable(true)->change();
            $table->unsignedInteger('receipt_type_id')->nullable(true)->index();

            $table->foreign('receipt_type_id')->references('id')->on('receipt_types')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            Schema::disableForeignKeyConstraints();
            $table->unsignedInteger('customer_id')->nullable(false)->change();
            $table->string('receiver', 100)->nullable(false)->change();
            $table->dropForeign(['receipt_type_id']);
            $table->dropColumn(['receipt_type_id']);
        });
    }
}

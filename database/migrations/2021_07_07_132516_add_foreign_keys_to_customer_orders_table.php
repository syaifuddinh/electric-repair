<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToCustomerOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('customer_order_status_id')->references('id')->on('customer_order_statuses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropForeign('customer_orders_customer_id_foreign');
            $table->dropForeign('customer_orders_quotation_id_foreign');
            $table->dropForeign('customer_orders_customer_order_status_id_foreign');
        });
    }
}

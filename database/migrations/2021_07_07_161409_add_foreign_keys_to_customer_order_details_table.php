<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToCustomerOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_order_details', function (Blueprint $table) {
            $table->foreign('header_id')->references('id')->on('customer_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_order_details', function (Blueprint $table) {
            $table->dropForeign('customer_order_details_header_id_foreign');
            $table->dropForeign('customer_order_details_item_id_foreign');
        });
    }
}

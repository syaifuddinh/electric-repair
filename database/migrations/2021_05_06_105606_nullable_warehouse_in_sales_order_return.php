<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NullableWarehouseInSalesOrderReturn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('sales_order_returns')
        ->delete();
        Schema::table('sales_order_returns', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable(true)->change();
            $table->unsignedInteger('sales_order_id')->nullable(true)->change();

             $table->unsignedInteger('company_id')->nullable(false)->index();
             $table->unsignedInteger('customer_id')->nullable(false)->index();

            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('RESTRICT');
            // $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        

        Schema::table('sales_order_returns', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable(true)->change();
            $table->unsignedInteger('sales_order_id')->nullable(true)->change();

            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id']);

            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id']);

        });
    }
}

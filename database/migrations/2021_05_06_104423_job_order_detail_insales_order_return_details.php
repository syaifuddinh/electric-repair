<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobOrderDetailInsalesOrderReturnDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('sales_order_return_details', 'item_detail_id')) {
            
            Schema::table('sales_order_return_details', function (Blueprint $table) {
                $table->dropForeign(['item_detail_id']);
                $table->dropColumn(['item_detail_id']);
            });
        }

        Schema::table('sales_order_return_details', function (Blueprint $table) {
            $table->unsignedInteger('job_order_detail_id')->nullable(false)->index();

            $table->foreign('job_order_detail_id')->references('id')->on('job_order_details')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_return_details', function (Blueprint $table) {
            $table->dropForeign(['job_order_detail_id']);
            $table->dropColumn(['job_order_detail_id']);
        });
    }
}

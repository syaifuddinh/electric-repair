<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterReceiveDataInJobOrderDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_order_details', function (Blueprint $table) {
            $table->datetime('receive_date')->nullable(true)->change();
        });
        Schema::table('warehouse_receipt_billings', function (Blueprint $table) {
            $table->datetime('billing_date')->nullable(true)->change();
            $table->datetime('new_receive_date')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_order_details', function (Blueprint $table) {
            $table->date('receive_date')->nullable(true)->change();
        });
        Schema::table('warehouse_receipt_billings', function (Blueprint $table) {
            $table->date('billing_date')->nullable(true)->change();
            $table->date('new_receive_date')->nullable(true)->change();
        });
    }
}

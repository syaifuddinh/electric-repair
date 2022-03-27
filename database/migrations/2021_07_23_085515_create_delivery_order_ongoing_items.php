<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryOrderOngoingItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_order_ongoing_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('delivery_order_driver_id')->nullable(false)->index();
            $table->unsignedInteger('job_order_id')->nullable(false)->index();

            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            $table->foreign('delivery_order_driver_id')->references('id')->on('delivery_order_drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_order_ongoing_jobs');
    }
}

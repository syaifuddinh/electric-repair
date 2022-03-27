<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryOrderStatusLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_order_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('delivery_order_driver_id')->nullable(false)->index();
            $table->unsignedInteger('job_status_id')->nullable(false)->index();
            $table->unsignedInteger('created_by')->nullable(false)->index();
            $table->datetime('created_at')->default(DB::raw('NOW()'))->nullable(false);

            $table->foreign('delivery_order_driver_id')->references('id')->on('delivery_order_drivers')->onDelete('cascade');
            $table->foreign('job_status_id')->references('id')->on('job_statuses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_order_status_logs');
    }
}

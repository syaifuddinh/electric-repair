<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteTransits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_order_transits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('header_id')->nullable(false)->index();
            $table->string('code', 50)->nullable(true);
            $table->string('route_name', 50)->nullable(true);

            $table->foreign('header_id')->references('id')->on('job_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_order_transits');
    }
}

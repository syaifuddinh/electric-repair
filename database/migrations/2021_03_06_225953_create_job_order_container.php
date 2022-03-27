<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobOrderContainer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_order_containers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('container_id')
            ->nullable(false)
            ->index();
            $table->unsignedInteger('job_order_id')
            ->nullable(false)
            ->index();
            $table->timestamps();

            $table->foreign('container_id')->references('id')->on('containers')->onDelete('cascade');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_order_containers');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_drivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('driver_id')->nullable(false)->index();
            $table->unsignedInteger('vehicle_id')->nullable(false)->index();

            $table->foreign('driver_id')->references('id')->on('contacts')->onDelete('RESTRICT');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_drivers');
    }
}

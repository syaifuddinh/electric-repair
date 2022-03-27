<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovementContainerDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('movement_container_details')) {
            Schema::create('movement_container_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('movement_container_id')->nullable(false)->index();
                $table->unsignedInteger('gate_in_container_id')->nullable(false)->index();
                $table->unsignedInteger('container_yard_destination_id')->nullable(false)->index();
                $table->timestamps();

            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movement_container_details');
    }
}

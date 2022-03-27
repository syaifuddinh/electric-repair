<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContainerInspectionDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('container_inspection_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('container_inspection_id')->nullable(false)->index();
            $table->unsignedInteger('container_part_id')->nullable(false)->index();
            $table->unsignedInteger('item_condition_id')->nullable(false)->index();
            $table->timestamps();

            $table->foreign('container_part_id')->references('id')->on('items')->onDelete('RESTRICT');
            $table->foreign('item_condition_id')->references('id')->on('item_conditions')->onDelete('RESTRICT');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('container_inspection_details');
    }
}

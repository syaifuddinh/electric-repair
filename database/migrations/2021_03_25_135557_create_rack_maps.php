<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRackMaps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('rack_maps')) {
            Schema::create('rack_maps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('rack_id')->nullable(false)->index();
                $table->unsignedInteger('warehouse_map_id')->nullable(false)->index();
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
        Schema::dropIfExists('rack_maps');
    }
}

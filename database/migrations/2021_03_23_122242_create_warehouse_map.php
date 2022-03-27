<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWarehouseMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('warehouse_maps')) {
            
            Schema::create('warehouse_maps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('warehouse_id')->nullable(false)->index();
                $table->integer('row')->nullable(false)->default(0)->index();
                $table->integer('column')->nullable(false)->default(0)->index();
                $table->integer('level')->nullable(false)->default(0)->index();
                $table->string('code', 30)->nullable(true);
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
        Schema::dropIfExists('warehouse_maps');
    }
}

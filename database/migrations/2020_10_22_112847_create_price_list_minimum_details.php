<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePriceListMinimumDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_list_minimum_details', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('price_list_id')->unsigned()->nullable();
            $table->integer('quotation_detail_id')->unsigned()->nullable();
            $table->float('price_per_kg', 10, 0);
            $table->integer('min_kg');
            $table->float('price_per_m3', 10, 0);
            $table->integer('min_m3');
            $table->float('price_per_item', 10, 0);
            $table->integer('min_item');
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('price_list_minimum_details');
    }
}

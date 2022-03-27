<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        if(Schema::hasTable('services')) {
            Schema::drop("services");
        }
        if(Schema::hasTable('service_types')) {
            Schema::drop("service_types");
        }
        if(Schema::hasTable('service_groups')) {
            Schema::drop("service_groups");
        }
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->string("name", 100);
            $table->double("price");
            $table->integer("estimated_time_in_day");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
}

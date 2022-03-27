<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColToPicking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pickings', function (Blueprint $table) {
          $table->unsignedInteger('staff_id')->nullable();

          $table->foreign('staff_id')->references('id')->on('contacts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickings', function (Blueprint $table) {
            //
        });
    }
}

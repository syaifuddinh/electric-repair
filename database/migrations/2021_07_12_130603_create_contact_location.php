<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('contact_id')->nullable(false)->index();
            $table->float('longitude', 8, 2);
            $table->float('latitude', 8, 2);
            $table->datetime('created_at')->nullable(false)->default(DB::raw("NOW()"));

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_locations');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContainerInspections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('container_inspections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->text('description')->nullable(true);
            $table->unsignedInteger('checker_id')->nullable(true)->index();
            $table->unsignedInteger('created_by')->nullable(false);
            $table->timestamps();

            $table->foreign('checker_id')->references('id')->on('contacts')->onDelete('RESTRICT');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('container_inspections');
    }
}

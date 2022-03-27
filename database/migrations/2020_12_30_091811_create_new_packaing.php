<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewPackaing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('packagings');
        Schema::create('packagings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('customer_id')->nullable(true)->index();
            $table->date('date')->nullable(true)->index();
            $table->text('description')->nullable(true);
            $table->smallInteger('is_approve')->nullable(false)->default(0);
            $table->unsignedInteger('created_by')->nullable(false)->index();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('RESTRICT');
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
        Schema::dropIfExists('packagings');
    }
}

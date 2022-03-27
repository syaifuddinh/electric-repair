<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->nullable(true);
            $table->string('mail_driver', 100)->nullable(true);
            $table->string('host', 100)->nullable(true);
            $table->integer('port')->nullable(false)->default(0);
            $table->string('username', 100)->nullable(true);
            $table->string('password', 100)->nullable(true);
            $table->string('encryption', 10)->nullable(true);
            $table->string('receipt_subject', 100)->nullable(true);
            $table->string('receipt_body', 100)->nullable(true);
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
        Schema::dropIfExists('emails');
    }
}

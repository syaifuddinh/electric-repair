<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subject', 100)->nullable(true);
            $table->text('destination')->nullable(false);
            $table->text('body')->nullable(true);
            $table->string('status', 10)->nullable(false);
            $table->text('description')->nullable(true);
            $table->datetime('created_at')->nullable(false);
            $table->unsignedInteger('created_by')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_logs');
    }
}

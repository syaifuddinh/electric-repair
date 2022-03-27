<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManifestEmailLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest_email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('manifest_id')->nullable(false)->index();
            $table->unsignedInteger('email_log_id')->nullable(false)->index();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manifest_email_logs');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorJobStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_job_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('priority')->nullable(false)->default(1)->index();
            $table->smallInteger('editable')->nullable(false)->default(0)->index();
            $table->string('name', 50)->nullable(false)->index();
            $table->string('slug', 50)->nullable(false)->index();
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
        Schema::dropIfExists('vendor_job_statuses');
    }
}

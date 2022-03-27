<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerOrderFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_order_files', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('header_id');
            $table->string('name');
            $table->string('file_name');
            $table->date('date_upload');
            $table->string('extension');
            $table->timestamps();
        });

        Schema::table('customer_order_files', function (Blueprint $table) {
            $table->foreign('header_id')->references('id')->on('customer_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_order_files', function (Blueprint $table) {
            $table->dropForeign('customer_order_files_header_id_foreign');
        });
        Schema::dropIfExists('customer_order_files');
    }
}

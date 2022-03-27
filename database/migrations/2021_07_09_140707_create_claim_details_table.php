<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClaimDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claim_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('header_id');
            $table->unsignedInteger('job_order_detail_id')->nullable();
            $table->unsignedInteger('commodity_id');
            $table->double('qty');
            $table->double('price');
            $table->double('total_price');
            $table->double('claim_qty');
            $table->double('claim_price');
            $table->double('claim_total_price');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('header_id')->references('id')->on('claims')->onDelete('cascade');
            $table->foreign('job_order_detail_id')->references('id')->on('job_order_details')->onDelete('restrict');
            $table->foreign('commodity_id')->references('id')->on('commodities')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claim_details', function (Blueprint $table){
            $table->dropForeign('claim_details_header_id_foreign');
            $table->dropForeign('claim_details_job_order_detail_id_foreign');
            $table->dropForeign('claim_details_commodity_id_foreign');
        });

        Schema::dropIfExists('claim_details');
    }
}

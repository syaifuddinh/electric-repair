<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClaimCategoryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claim_category_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('claim_detail_id');
            $table->timestamps();

            $table->foreign('claim_detail_id')->references('id')->on('claim_details')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('claim_categories')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claim_category_details', function (Blueprint $table){
            $table->dropForeign('claim_category_details_claim_detail_id_foreign');
            $table->dropForeign('claim_category_details_category_id_foreign');
        });

        Schema::dropIfExists('claim_category_details');
    }
}

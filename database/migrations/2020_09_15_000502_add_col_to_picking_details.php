<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColToPickingDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('picking_details', function (Blueprint $table) {
          $table->string('lot_no')->nullable();
          $table->string('um')->nullable();
          $table->timestamp('delivery_schedule')->nullable();
          $table->timestamp('time_staging')->nullable();
          $table->timestamp('time_finish')->nullable();
          $table->double('qty_delivered')->default(0);
          $table->unsignedInteger('category_id')->nullable();
          $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('picking_details', function (Blueprint $table) {
          $table->dropForeign('picking_details_category_id_foreign');
          $table->dropColumn(['lot_no','um','delivery_schedule','time_staging','time_finish','qty_delivered','category_id']);
        });
    }
}

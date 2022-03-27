<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PickingDetailInDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manifest_details', function (Blueprint $table) {
            $table->unsignedInteger('job_order_detail_id')->nullable(true)->change();
            $table->unsignedInteger('picking_detail_id')->nullable(true);
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manifest_details', function (Blueprint $table) {
            $table->unsignedInteger('job_order_detail_id')->nullable(false)->change();
            $table->dropColumn(['picking_detail_id']);
            $table->dropColumn(['warehouse_receipt_detail_id']);
        });
    }
}

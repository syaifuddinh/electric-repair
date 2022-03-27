<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorJobStatusToJobOrderCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_order_costs', function (Blueprint $table) {
            $table->unsignedInteger('vendor_job_status_id')->nullable(true)->index();
            $table->foreign('vendor_job_status_id')->references('id')->on('vendor_job_statuses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_order_costs', function (Blueprint $table) {
            $table->dropForeign(['vendor_job_status_id']);
            $table->dropColumn(['vendor_job_status_id']);
        });
    }
}

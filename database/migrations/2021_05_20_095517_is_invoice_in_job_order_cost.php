<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsInvoiceInJobOrderCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('job_order_costs', 'is_invoice')) {
            Schema::table('job_order_costs', function (Blueprint $table) {
                $table->smallInteger('is_invoice')->nullable(false)->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('job_order_costs', 'is_invoice')) {
            Schema::table('job_order_costs', function (Blueprint $table) {
                $table->dropColumn(['is_invoice']);
            });
        }
    }
}

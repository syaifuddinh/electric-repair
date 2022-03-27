<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsAutoInvoiceInCostType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->smallInteger('is_auto_invoice')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->dropColumn(['is_auto_invoice']);
        });
    }
}

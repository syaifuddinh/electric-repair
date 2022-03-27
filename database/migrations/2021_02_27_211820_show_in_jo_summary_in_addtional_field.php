<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ShowInJoSummaryInAddtionalField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('additional_fields', function (Blueprint $table) {
            $table->smallInteger('show_in_job_order_summary')->nullable(false)->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('additional_fields', function (Blueprint $table) {
            $table->dropColumn(['show_in_job_order_summary']);
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableNameInTypeTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('type_transactions', function (Blueprint $table) {
            $table->string('table_name')->nullable(true);
        });
        DB::table('type_transactions')
        ->whereSlug('manifest')
        ->update(['table_name' => 'manifests']);

        DB::table('type_transactions')
        ->whereSlug('jobOrder')
        ->update(['table_name' => 'job_orders']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('type_transactions', function (Blueprint $table) {
            $table->dropColumn(['table_name']);
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountCashToAccountDefaultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_defaults', function (Blueprint $table) {
            $table->unsignedInteger('account_cash')->nullable();

            $table->foreign('account_cash')->references('id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_defaults', function (Blueprint $table) {
            $table->dropForeign(['account_cash']);
            $table->dropColumn('account_cash');
        });
    }
}

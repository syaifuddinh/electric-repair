<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPendapatanReimburseToAccountDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     
    public function up()
    {
        Schema::table('account_defaults', function (Blueprint $table) {
          $table->unsignedInteger('pendapatan_reimburse')->nullable();
          $table->foreign('pendapatan_reimburse')->references('id')->on('accounts')->onDelete('SET NULL');
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
          $table->dropForeign('account_defaults_pendapatan_reimburse_foreign');
          $table->dropColumn(['pendapatan_reimburse']);
        });
    }
}

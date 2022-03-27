<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveReceiptListIdInReturs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('returs', 'receipt_list_id')) {
            Schema::table('returs', function (Blueprint $table) {
                $table->dropForeign(['receipt_list_id']);
                $table->dropColumn(['receipt_list_id']);
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
        if(Schema::hasColumn('returs', 'receipt_list_id')) {
            Schema::table('returs', function (Blueprint $table) {
                $table->unsignedIntegeri('receipt_list_id')->nullable(true);
            });
        }
    }
}

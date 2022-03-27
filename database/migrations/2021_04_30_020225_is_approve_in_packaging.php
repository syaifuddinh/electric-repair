<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsApproveInPackaging extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('packagings', 'is_approve')) {
            Schema::table('packagings', function(Blueprint $table){
                $table->smallInteger('is_approve')->default(0)->index();
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
        //
        if(Schema::hasColumn('packagings', 'is_approve')) {
            Schema::table('packagings', function(Blueprint $table){
                $table->dropColumn(['is_approve']);
            });
        }
    }
}

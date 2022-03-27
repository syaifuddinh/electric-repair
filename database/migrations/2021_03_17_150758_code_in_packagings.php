<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CodeInPackagings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('packagings', 'warehouse_id')) {

            Schema::table('packagings', function (Blueprint $table) {
                $table->unsignedInteger('warehouse_id')->nullable(false)->index();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            });
        }

        if(!Schema::hasColumn('packagings', 'company_id')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->unsignedInteger('company_id')->nullable(false)->index();
                $table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            });
        }

        if(!Schema::hasColumn('packagings', 'code')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->string('code', 100)->nullable(true)->index();

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
        DB::table('packaging_new_items')
        ->delete();
        DB::table('packaging_old_items')
        ->delete();
        DB::table('packagings')
        ->delete();
        if(Schema::hasColumn('packagings', 'warehouse_id')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn(['warehouse_id']);
            });
        }
        if(Schema::hasColumn('packagings', 'code')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->dropColumn(['code']);
            });
        }
        
        if(Schema::hasColumn('packagings', 'company_id')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn(['company_id']);
            });
        }
    }
}

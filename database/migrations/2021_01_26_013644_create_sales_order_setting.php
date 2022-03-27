<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrderSetting extends Migration
{
    protected $params = '{"settings":[{"name":"Layanan yang mewakili aktivitas penjualan ?","slug":"sales_service_id","type":"selectLookup","value":0,"table":"services","placeholder":"Pilih Layanan"}]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')
        ->insert([
            'name' => 'Sales Order',
            'slug' => 'sales_order',
            'content' => $this->params
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('settings')
        ->whereSlug('sales_order')
        ->delete();
    }
}

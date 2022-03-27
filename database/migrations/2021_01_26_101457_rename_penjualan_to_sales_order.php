<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePenjualanToSalesOrder extends Migration
{
    protected $slug = 'salesOrder'; 
    protected $table = 'type_transactions'; 
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table($this->table)
        ->whereSlug($this->slug)
        ->update(['name' => 'Sales Order']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table($this->table)
        ->whereSlug($this->slug)
        ->update(['name' => 'Penjualan']);
    }
}

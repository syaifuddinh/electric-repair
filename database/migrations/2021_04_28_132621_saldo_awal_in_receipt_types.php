<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SaldoAwalInReceiptTypes extends Migration
{
    protected $code = 'ro9';
    protected $name = 'Saldo Awal';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('receipt_types')
        ->insert([
            'code' => $this->code,
            'name' => $this->name
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('receipt_types')
        ->whereCode($this->code)
        ->delete();
    }
}

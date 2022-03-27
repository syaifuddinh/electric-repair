<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCashTransactionTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_transaction_stream_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 10)->nullable(false)->index();
            $table->string('name', 50)->nullable(true);
        });

        DB::table('cash_transaction_stream_types')
        ->insert([
            [
                'id' => 1,
                'slug' => 'inbound',
                'name' => 'Masuk',
            ],
            [
                'id' => 2,
                'slug' => 'outbound',
                'name' => 'Keluar'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_transaction_types');
    }
}

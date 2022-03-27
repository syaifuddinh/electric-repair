<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCashTransactionCostStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_transaction_cost_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 20)->nullable(false)->index();
            $table->string('name', 50)->nullable(false);
        });

        DB::table('cash_transaction_cost_statuses')
        ->insert([
            [
                'slug' => 'draft',
                'name' => 'Belum Persetujuan'
            ],
            [
                'slug' => 'approved',
                'name' => 'Disetujui (Belum Posting)'
            ],
            [
                'slug' => 'finished',
                'name' => 'Selesai'
            ],
            [
                'slug' => 'rejected',
                'name' => 'Ditolak'
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
        Schema::dropIfExists('cash_transaction_cost_statuses');
    }
}

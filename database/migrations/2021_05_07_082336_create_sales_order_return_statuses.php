<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrderReturnStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_return_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 30);
        });

        DB::table('sales_order_return_statuses')
        ->insert([
            [
                'id' => 1,
                'name' => 'Draft'
            ],
            [
                'id' => 2,
                'name' => 'Approved'
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
        Schema::dropIfExists('sales_order_return_statuses');
    }
}

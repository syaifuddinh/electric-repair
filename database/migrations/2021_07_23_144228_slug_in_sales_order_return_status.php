<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SlugInSalesOrderReturnStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_order_return_statuses', function (Blueprint $table) {
            $table->string('slug', 30)->nullable(true)->index();
        });

        DB::table('sales_order_return_statuses')->delete();
        DB::table('sales_order_return_statuses')
        ->insert([
            [
                'id' => 1,
                'slug' => 'requested',
                'name' => 'Requested',
            ],
            [
                'id' => 2,
                'slug' => 'finished',
                'name' => 'Finished'
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
        Schema::table('sales_order_return_statuses', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
}

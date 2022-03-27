<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrderStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 20)->nullable(false)->index();
            $table->string('name', 50)->nullable(false)->index();
        });

        DB::table('purchase_order_statuses')
        ->insert([
            [
                'slug' => 'requested',
                'name' => 'Requested'
            ],
            [
                'slug' => 'approved',
                'name' => 'Approved'
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_statuses');
    }
}

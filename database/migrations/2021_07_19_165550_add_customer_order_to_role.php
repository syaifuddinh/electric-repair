<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCustomerOrderToRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = [
            [
                'name' => 'Customer Order',
                'slug' => 'sales.customer_order',
                'deep' => 2,
                'urut' => 51200000000
            ],
            [
                'name' => 'Add',
                'slug' => 'sales.customer_order.add',
                'deep' => 3,
                'urut' => 51201000000
            ],
            [
                'name' => 'Edit',
                'slug' => 'sales.customer_order.edit',
                'deep' => 3,
                'urut' => 51202000000
            ],
            [
                'name' => 'Detail',
                'slug' => 'sales.customer_order.detail',
                'deep' => 3,
                'urut' => 51203000000
            ],
            [
                'name' => 'Delete',
                'slug' => 'sales.customer_order.delete',
                'deep' => 3,
                'urut' => 51204000000
            ],
            [
                'name' => 'Approve',
                'slug' => 'sales.customer_order.approve',
                'deep' => 3,
                'urut' => 51205000000
            ],
        ];

        DB::table('roles')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('roles')->where('slug','LIKE', 'sales.customer_order%')->delete();
    }
}

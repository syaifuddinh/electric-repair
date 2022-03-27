<?php

use App\Model\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoApproveRejectRole extends Migration
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
                'name' => 'Approve',
                'slug' => 'sales.sales_order.approve',
                'deep' => 3,
                'urut' => 50104000000,
                'created_at' => Carbon::now()
            ],
            [
                'name' => 'Reject',
                'slug' => 'sales.sales_order.reject',
                'deep' => 3,
                'urut' => 50104100000,
                'created_at' => Carbon::now()
            ],
        ];
        Role::insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::whereIn('slug', ['sales.sales_order.approve', 'sales.sales_order.reject'])->delete();
    }
}

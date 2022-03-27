<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateSalesOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->timestamps();
        });

        $data = [
            [
                'slug' => 'waiting_for_approval',
                'name' => 'Menunggu Persetujuan',
                'created_at' => Carbon::now()
            ],
            [
                'slug' => 'waiting_for_payment',
                'name' => 'Menunggu Pembayaran',
                'created_at' => Carbon::now()
            ],
            [
                'slug' => 'approved',
                'name' => 'Disetujui',
                'created_at' => Carbon::now()
            ],
            [
                'slug' => 'rejected',
                'name' => 'Ditolak',
                'created_at' => Carbon::now()
            ],
        ];
        DB::table('sales_order_statuses')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_order_statuses');
    }
}

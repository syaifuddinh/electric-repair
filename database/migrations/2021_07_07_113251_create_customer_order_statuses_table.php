<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCustomerOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
        });

        DB::table('customer_order_statuses')
        ->insert([
            [
                'slug' => 'draft',
                'name' => 'Pengajuan'
            ],
            [
                'slug' => 'approved',
                'name' => 'Disetujui'
            ],
            [
                'slug' => 'over-limitation',
                'name' => 'Melebihi Limit Piutang'
            ],
            [
                'slug' => 'rejected',
                'name' => 'Ditolak'
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
        Schema::dropIfExists('customer_order_statuses');
    }
}

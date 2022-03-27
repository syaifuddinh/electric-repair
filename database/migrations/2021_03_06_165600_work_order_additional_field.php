<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WorkOrderAdditionalField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('type_transactions')
        ->whereSlug('workOrder')
        ->update([
            'is_customable_field' => 1,
            'table_name' => 'work_orders'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('type_transactions')
        ->whereSlug('workOrder')
        ->update([
            'is_customable_field' => 0,
            'table_name' => null
        ]);
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceiptQualityStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipt_quality_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 30)->nullable(false)->index();
            $table->string('name', 30)->nullable(false)->index();
        });

        DB::table('receipt_quality_statuses')
        ->insert([
            [
                'slug' => 'isDraft',
                'name' => 'Requested'
            ],
            [
                'slug' => 'isApproved',
                'name' => 'Approved'
            ],
            [
                'slug' => 'isRejected',
                'name' => 'Rejected'
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
        Schema::dropIfExists('receipt_quality_statuses');
    }
}

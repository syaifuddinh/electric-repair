<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovementContainers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('movement_containers')) {

            DB::table('type_transactions')
            ->insert([
                'name' => 'Movement Container',
                'slug' => 'movementContainer'
            ]);
            Schema::create('movement_containers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('company_id')->nullable(false)->index();
                $table->datetime('date')->nullable(false)->index();
                $table->string('code', 200)->nullable(true)->index();
                $table->unsignedInteger('operator_id')->nullable(false)->index();
                $table->unsignedInteger('created_by')->nullable(false)->index();
                $table->unsignedInteger('status')->nullable(false)->index();
                $table->text('description')->nullable(true);

                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('RESTRICT');
                $table->foreign('operator_id')->references('id')->on('contacts')->onDelete('RESTRICT');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('RESTRICT');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movement_containers');
        DB::table('type_transactions')
        ->whereSlug('movementContainer')
        ->delete();
    }
}

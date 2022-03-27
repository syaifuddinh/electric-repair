<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGateInContaniner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gate_in_containers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->nullable(false)->index();
            $table->unsignedInteger('owner_id')->nullable(true)->index();
            $table->unsignedInteger('container_id')->nullable(true)->index();
            $table->string('code', 100)->nullable(false)->index();
            $table->datetime('date')->nullable(false)->index();
            $table->text('description')->nullable(true);
            $table->string('no_container', 100)->nullable(false);
            $table->unsignedInteger('container_type_id')->nullable(false)->index();
            $table->unsignedInteger('status')->nullable(false)->index();
            $table->unsignedInteger('created_by')->nullable(false)->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('RESTRICT');
            $table->foreign('owner_id')->references('id')->on('contacts')->onDelete('RESTRICT');
            $table->foreign('container_type_id')->references('id')->on('container_types')->onDelete('RESTRICT');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('RESTRICT');
        });

        $params = [];
        $params['name'] = 'Gate in Container';
        $params['slug'] = 'gateInContainer';
        DB::table('type_transactions')->insert($params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gate_in_containers');
        DB::table('type_transactions')->whereSlug('gateInContainer')->delete();
    }
}

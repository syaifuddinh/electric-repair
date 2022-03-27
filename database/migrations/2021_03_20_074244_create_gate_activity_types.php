<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGateActivityTypes extends Migration
{
    protected $params = [
        ['slug' => 'containerIn', 'name' => 'Container In'],
        ['slug' => 'containerOut', 'name' => 'Container Out'],
        ['slug' => 'containerInOut', 'name' => 'Container In And Out'],
        ['slug' => 'visitor', 'name' => 'Visitor'],
        ['slug' => 'other', 'name' => 'Other'],
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('gate_activity_types')) {
            Schema::create('gate_activity_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('slug', 30)->nullable(false)->index();
                $table->string('name', 100)->nullable(false)->index();
                $table->timestamps();
            });
        }
        DB::table('gate_activity_types')
        ->insert($this->params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gate_activity_types');
    }
}

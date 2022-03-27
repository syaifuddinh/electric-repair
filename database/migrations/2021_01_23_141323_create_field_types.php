<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFieldTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('field_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 15)->nullable(false)->index();
            $table->string('name', 50)->nullable(false);
        });

        $params = [];
        $params[] = [];
        $params[] = [];

        $params[0]['slug'] = 'text';
        $params[0]['name'] = 'Text';
        $params[1]['slug'] = 'number';
        $params[1]['name'] = 'Number';

        DB::table('field_types')
        ->insert($params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('field_types');
    }
}

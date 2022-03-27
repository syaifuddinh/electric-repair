<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonths extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('months', function (Blueprint $table) {
            $table->increments('id');
            $table->smallInteger('sequence')->nullable(false)->default(0)->index();
            $table->string('name', 20)->nullable(false);
        });

        DB::table('months')
        ->insert([
            [
                'sequence' => 1,
                'name' => 'Januari'
            ],
            [
                'sequence' => 2,
                'name' => 'Februari'
            ],
            [
                'sequence' => 3,
                'name' => 'Maret'
            ],
            [
                'sequence' => 4,
                'name' => 'April'
            ],
            [
                'sequence' => 5,
                'name' => 'Mei'
            ],
            [
                'sequence' => 6,
                'name' => 'Juni'
            ],
            [
                'sequence' => 7,
                'name' => 'Juli'
            ],
            [
                'sequence' => 8,
                'name' => 'Agustus'
            ],
            [
                'sequence' => 9,
                'name' => 'September'
            ],
            [
                'sequence' => 10,
                'name' => 'Oktober'
            ],
            [
                'sequence' => 11,
                'name' => 'November'
            ],
            [
                'sequence' => 12,
                'name' => 'Desember'
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
        Schema::dropIfExists('months');
    }
}

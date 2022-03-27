<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStokOpnameStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stok_opname_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable(false)->index();
            $table->string('name')->nullable(false);
        });

        DB::table('stok_opname_statuses')
        ->insert([
            [
                'slug' => 'draft',
                'name' => 'Pengajuan'
            ],
            [
                'slug' => 'approved',
                'name' => 'Disetujui'
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
        Schema::dropIfExists('stok_opname_statuses');
    }
}

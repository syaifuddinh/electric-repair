<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComponentGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('type_transactions', function (Blueprint $table) {
            $table->smallInteger('is_customable_field')->nullable(false)->default(0);
        });

        $params = [];
        $params['is_customable_field'] = 1;
        DB::table('type_transactions')
        ->whereSlug('manifest')
        ->update($params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('type_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_customable_field']);
        });

    }
}

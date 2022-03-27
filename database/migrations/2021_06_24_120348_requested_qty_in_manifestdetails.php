<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RequestedQtyInManifestdetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manifest_details', function(Blueprint $table)
        {
            $table->double('requested_qty')->nullable(false)->default(0);
            $table->double('discharged_qty')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manifest_details', function(Blueprint $table)
        {
            $table->dropColumn(['requested_qty']);
            $table->dropColumn(['discharged_qty']);
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SlugInJobStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_statuses', function (Blueprint $table) {
            $table->string('slug', 50)->nullable(true)->index();
        });

        DB::table('job_statuses')->whereUrut(1)->update([
            'slug' => 'startedByVendor' 
        ]);

        DB::table('job_statuses')->whereUrut(2)->update([
            'slug' => 'startedByDriver' 
        ]);

        DB::table('job_statuses')->whereUrut(3)->update([
            'slug' => 'jobReceived' 
        ]);

        DB::table('job_statuses')->whereUrut(4)->update([
            'slug' => 'pickingStarted' 
        ]);

        DB::table('job_statuses')->whereUrut(5)->update([
            'slug' => 'arrivedAtPickingLocation' 
        ]);

        DB::table('job_statuses')->whereUrut(6)->update([
            'slug' => 'itemLoaded' 
        ]);

        DB::table('job_statuses')->whereUrut(7)->update([
            'slug' => 'departedFromPickingLocation' 
        ]);

        DB::table('job_statuses')->whereUrut(8)->update([
            'slug' => 'arrivedAtDischargeLocation' 
        ]);

        DB::table('job_statuses')->whereUrut(9)->update([
            'slug' => 'dischargeStarted' 
        ]);

        DB::table('job_statuses')->whereUrut(10)->update([
            'slug' => 'dischargeFinished' 
        ]);
        
        DB::table('job_statuses')->whereUrut(11)->update([
            'slug' => 'jobFinished' 
        ]);
        
        DB::table('job_statuses')->whereUrut(12)->update([
            'slug' => 'aborted' 
        ]);
        DB::table('job_statuses')->whereUrut(13)->update([
            'slug' => 'rejected' 
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_statuses', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
}

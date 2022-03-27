<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MustUploadFileInJobStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_statuses', function (Blueprint $table) {
            $table->smallInteger('must_upload_file')->default(0)->nullable(false);
        });

        DB::table('job_statuses')
        ->whereSlug('dischargeFinished')
        ->update([
            'must_upload_file' => 1
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
            $table->dropColumn(['must_upload_file']);
        });
    }
}

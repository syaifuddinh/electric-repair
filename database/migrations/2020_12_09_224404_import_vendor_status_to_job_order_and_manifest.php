<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImportVendorStatusToJobOrderAndManifest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $status = DB::table('vendor_job_statuses')
        ->whereSlug('draft')
        ->first();

        if($status) {
            DB::table('job_order_costs')
            ->update([
                'vendor_job_status_id' => $status->id
            ]);
            DB::table('manifest_costs')
            ->update([
                'vendor_job_status_id' => $status->id
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
            DB::table('job_order_costs')
            ->update([
                'vendor_job_status_id' => null
            ]);
            DB::table('manifest_costs')
            ->update([
                'vendor_job_status_id' => null
            ]);
    }
}

<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;

class ManifestCost
{
    public static function storeVendorJob($id) {
        $status = DB::table('vendor_job_statuses')
        ->whereSlug('draft')
        ->first();
        if($status) {
            DB::table('manifest_costs')
            ->whereId($id)
            ->update([
                'vendor_job_status_id' => $status->id
            ]);
        }
    }
}

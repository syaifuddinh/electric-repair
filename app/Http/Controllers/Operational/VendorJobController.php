<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use DB;
use Response;

class VendorJobController extends Controller
{
    public function storeStatus(Request $request, $source, $id) {
        $request->validate([
            'vendor_job_status_id' => 'required',
        ]);

        if($source == 'job_order') {
            DB::table('job_order_costs')
            ->whereId($id)
            ->update([
                'vendor_job_status_id' => $request->vendor_job_status_id
            ]);
        } else if($source == 'manifest') {
            DB::table('manifest_costs')
            ->whereId($id)
            ->update([
                'vendor_job_status_id' => $request->vendor_job_status_id
            ]);
        }

        return response()->json(['message' => 'Data successfully saved']);  
    }
}

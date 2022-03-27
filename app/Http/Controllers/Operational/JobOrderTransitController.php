<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use DB;

class JobOrderTransitController extends Controller
{
    public function store(Request $request, $job_order_id) {
        $request->validate([
            'code' => 'required',
            'route_name' => 'required'
        ]);
        DB::table('job_order_transits')
        ->insert([
            'header_id' => $job_order_id,
            'code' => $request->code,
            'date' => dateDB($request->date),
            'route_name' => $request->route_name
        ]);
        return response()->json(['message' => 'Data successfully saved']);
    }

    public function update(Request $request, $job_order_id, $id) {
        $request->validate([
            'code' => 'required',
            'route_name' => 'required'
        ]);
        try {
            $exist = DB::table('job_order_transits')
            ->whereId($id)
            ->count('id');
            if($exist == 0) {
                throw new Exception('Data tidak ditemukan');
            }
            DB::table('job_order_transits')
            ->whereId($id)
            ->update([
                'code' => $request->code,
                'date' => dateDB($request->date),
                'route_name' => $request->route_name
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 411);
        }
        return response()->json(['message' => 'Data successfully saved']);
    }

    public function destroy(Request $request, $job_order_id, $id) {
        try {
            $exist = DB::table('job_order_transits')
            ->whereId($id)
            ->count('id');
            if($exist == 0) {
                throw new Exception('Data tidak ditemukan');
            }
            DB::table('job_order_transits')
            ->whereId($id)
            ->delete();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 411);
        }
        return response()->json(['message' => 'Data successfully saved']);
    }
}

<?php

namespace App\Http\Controllers\Api\v5;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Contact;
use App\Abstracts\Vehicle\VehicleDriver;
use DB;

class VehicleController extends Controller
{
    /*
      Date : 09-07-2021
      Description : Melihat daftar kendaraan yang di-assign ke driver 
      Developer : Didin
      Status : Create
    */
    public function index(Request $request) {
        $resp['message'] = 'OK';

        $dt = Contact::availableVehicleQuery([
            'contact_id' => auth()->user()->contact_id
        ]);
        $dt = $dt->select(
            'vehicles.id',
            'vehicles.code',
            'vehicles.nopol AS police_no'
        );
        $dt = $dt->get();
        $resp['data'] = $dt;

        return response()->json($resp);
    }

    /*
      Date : 09-07-2021
      Description : Menyimpan kendaraan default yang akan dipakai driver 
      Developer : Didin
      Status : Create
    */
    public function store(Request $request) {
        $resp = [];
        $resp['message'] = 'OK';
        $statusCode = 200;
        DB::beginTransaction();
        try {
            $user = auth()->user();
            if(!$user->contact_id) {
                throw new Exception('Only driver can be select vehicle');
            } else {
                VehicleDriver::store($user->contact_id, $request->vehicle_id);
            }
            DB::commit();
        } catch(\Exception $e) {
            DB::rollback();
            $statusCode = 421;
            $resp['message'] = $e->getMessage();
        }

        return response()->json($resp, $statusCode);                        
    }
}

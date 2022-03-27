<?php

namespace App\Http\Controllers\Api\v5;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Contact\ContactLocation;
use Exception;
use App\Abstracts\Operational\DeliveryOrderDriver;
use App\Abstracts\Setting\User;
use App\Abstracts\Vehicle\VehicleDriver;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{

    /*
      Date : 09-07-2021
      Description : Login 
      Developer : Didin
      Status : Create
    */
    public function storeLocation(Request $request)
    {
        $resp = [];
        $resp['message'] = 'OK';
        $statusCode = 200;
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['contact_id'] = auth()->user()->contact_id;
            ContactLocation::store($params);
            DeliveryOrderDriver::setJourneyDistance($params['contact_id']);
            DB::commit();
        } catch(\Exception $e) {
            DB::rollback();
            $statusCode = 421;
            $resp['message'] = $e->getMessage();
        }

        return response()->json($resp, $statusCode);
    }

    /*
      Date : 09-07-2021
      Description : Logout 
      Developer : Didin
      Status : Create
    */
    public function logout(Request $request)
    {
        $resp = $this->auth->logout($request);

        return $resp;
    }

    /*
      Date : 09-07-2021
      Description : Ubah password 
      Developer : Didin
      Status : Edit
    */
    public function changePassword(Request $request)
    {
        $msg = 'OK';
        $status_code = 200;

        DB::beginTransaction();
        try {
            $id = auth()->id();
            User::changePassword($request->input('password'), $request->input('password_confirm'), $id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            $status_code = 421;
        }

        $data = [];
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 09-07-2021
      Description : Menampilkan summary pengiriman barang 
      Developer : Didin
      Status : Edit
    */
    public function showShipmentSummary(Request $request)
    {
        $resp['message']  = 'OK';
        $user = auth()->user();

        $vehicle = VehicleDriver::showVehicleByDriver($user->contact_id);
        $dt = DeliveryOrderDriver::indexShipmentSummary($user->contact_id, $vehicle->id);
        $resp['data'] = $dt;

        return response()->json($resp);
    }
}

<?php

namespace App\Http\Controllers\Api\v5;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Setting\User;
use App\Abstracts\Contact;
use App\Abstracts\Vehicle\VehicleDriver;
use DB;
use Exception;

class AuthController extends Controller
{
    public function __construct() {
        $auth = new \App\Http\Controllers\Api\v4\CustomerController();
        $this->auth = $auth;
    }

       public function checkToken()
    {
        return response()->json(auth()->user());
    }


    /*
      Date : 09-07-2021
      Description : Login 
      Developer : Didin
      Status : Create
    */
    public function getAuth(Request $request) {
        $resp['message'] = 'OK';
        $user = User::show(auth()->id());

        $vehicle = VehicleDriver::showVehicleByDriver($user->contact_id);

        $dt = [];
        $dt['id'] = $user->id;
        $dt['name'] = $user->name;
        $dt['is_active'] = $user->is_active ? true : false;
        $dt['username'] = $user->username;
        $dt['address'] = $user->address;
        $dt['vehicle_id'] = $vehicle->id ?? null;
        $dt['vehicle_code'] = $vehicle->code ?? null;
        $dt['vehicle_police_no'] = $vehicle->nopol ?? null;
        $dt['another_job_qty'] = 0;
        $dt['score'] = 0;
        $resp['data'] = $dt;

        return response()->json($resp);
    }

    /*
      Date : 09-07-2021
      Description : Login 
      Developer : Didin
      Status : Create
    */
    public function login(Request $request)
    {
        $params = [];
        $params['username'] = $request->username;
        $params['password'] = $request->password;
        $params = new Request($params);
        $user = $this->auth->login_user($params);
        $dt = $user->getData();
        $resp = [];
        $resp['message'] = 'OK';
        $statusCode = 200;
        try {
            if(($dt->id ?? null)) {
                if(!$dt->contact_id) {
                    throw new Exception('Only driver can be login');
                } else {
                    $contact = Contact::show($dt->contact_id);
                    if(!$contact->is_driver) {
                        throw new Exception('Only driver can be login');
                    }
                }

                $resp['access_token'] = [];
                $resp['access_token']['access_token'] = $dt->api_token;
                $resp['access_token']['token'] = [];
                $resp['access_token']['token']['id'] = $dt->id;
                $resp['access_token']['token']['user_id'] = $dt->id;
                $resp['access_token']['token']['client_id'] = $dt->id;
                $resp['access_token']['token']['name'] = $dt->name;
                $resp['access_token']['token']['expires_date'] = $dt->due_date;
            } else {
                throw new Exception($dt->message);
            }
        } catch(\Exception $e) {
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
}

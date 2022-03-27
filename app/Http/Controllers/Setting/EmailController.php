<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Setting\Email;
use Response;
use DB;

class EmailController extends Controller
{ 

    /*
      Date : 16-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            Email::store($request->all());
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 16-03-2021
      Description : Menampilkan detail data
      Developer : Didin
      Status : Create
    */
    public function index()
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = Email::show();
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 16-03-2021
      Description : Menampilkan chips untuk konten template email shipment
      Developer : Didin
      Status : Create
    */
    public function indexShipmentChip()
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = Email::indexShipmentChip();
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }
}

<?php

namespace App\Http\Controllers\Api\v4\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Operational\Stuffing;
use App\Abstracts\Operational\StuffingDetail;
use DB;
use Response;
use Exception;

class StuffingController extends Controller
{
    public function index()
    {
        //
    }


    /*
      Date : 11-02-2021
      Description : Menyimpan stuffing order
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $request->source = 'picking_order';
        $request->is_full = 1;
        $m = new \App\Http\Controllers\Operational\ManifestFTLController();
        $dt = $m->store($request);

        return $dt;
    }

    /*
      Date : 11-02-2021
      Description : Menampilkan detail stuffing
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
        $status_code = 200;
        try {
            $dt = Stuffing::show($id);
            $msg = 'OK';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }

    public function indexDetail($id)
    {
        $status_code = 200;
        try {
            $dt = StuffingDetail::index($id);
            $msg = 'OK';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }

    public function storeDetail(Request $request, $id)
    {
        $status_code = 200;
        try {
            $params = $request;
            $dt = StuffingDetail::store($params, $id);
            $msg = 'Data successfully saved';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }

    /*
      Date : 12-02-2021
      Description : Menghapus barang pada detail stuffing
      Developer : Didin
      Status : Create
    */
    public function destroyDetail($manifest_id, $id)
    {
        $status_code = 200;
        try {
            Manifest::validate($manifest_id, 'picking_order');
            $dt = StuffingDetail::destroy($id);
            $msg = 'Data successfully deleted';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }

    /*
      Date : 12-02-2021
      Description : Meng-update stuffing
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $manifest_id)
    {
        $status_code = 200;
        try {
            Manifest::validate($manifest_id, 'picking_order');
            $dt = Stuffing::update($request->all(), $manifest_id);
            $msg = 'Data successfully saved';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status_code = 200;
        try {
            $dt = Stuffing::destroy($id);
            $msg = 'Data successfully deleted';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }
}

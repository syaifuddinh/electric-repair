<?php

namespace App\Http\Controllers\Api\v4\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Operational\Crossdocking;
use App\Abstracts\Operational\CrossdockingDetail;
use DB;
use Response;
use Exception;

class CrossdockingController extends Controller
{
    public function index()
    {
        //
    }


    /*
      Date : 11-02-2021
      Description : Menyimpan crossdocking order
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $request->is_crossdocking = 1;
        $request->is_full = 1;
        $m = new \App\Http\Controllers\Operational\ManifestFTLController();
        $dt = $m->store($request);

        return $dt;
    }

    /*
      Date : 11-02-2021
      Description : Menampilkan detail crossdocking
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
        $status_code = 200;
        try {
            $dt = Crossdocking::show($id);
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

    /*
      Date : 11-02-2021
      Description : Menampilkan daftar barang pada 
                    stuffing order
      Developer : Didin
      Status : Create
    */
    public function indexDetail($id)
    {
        $status_code = 200;
        try {
            $dt = CrossdockingDetail::index($id);
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

    /*
      Date : 11-02-2021
      Description : Menambah barang pada 
                    stuffing order
      Developer : Didin
      Status : Create
    */
    public function storeDetail(Request $request, $id)
    {
        $status_code = 200;
        try {
            $params = $request;
            $dt = CrossdockingDetail::store($params, $id);
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
      Description : Menghapus barang pada detail crossdocking
      Developer : Didin
      Status : Create
    */
    public function destroyDetail($manifest_id, $id)
    {
        $status_code = 200;
        try {
            Manifest::validate($manifest_id, 'picking_order');
            $dt = CrossdockingDetail::destroy($id);
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
      Description : Meng-update crossdocking
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $manifest_id)
    {
        $status_code = 200;
        try {
            Manifest::validate($manifest_id, null, 1);
            $dt = Crossdocking::update($request->all(), $manifest_id);
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
            $dt = Crossdocking::destroy($id);
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

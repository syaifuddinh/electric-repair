<?php

namespace App\Http\Controllers\Depo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Depo\ContainerInspection;
use App\Abstracts\Depo\ContainerInspectionDetail;
use Response;
use DB;

class ContainerInspectionController extends Controller
{
    public function index(Request $request)
    {
        $dt = ContainerInspection::index();
        $data['message'] = 'OK';
        $data['data'] = $dt;
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required',
            'checker_id' => 'required'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $container_inspection_id = ContainerInspection::store($request->all());
            ContainerInspectionDetail::storeMultiple($request->detail, $container_inspection_id);
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
    public function show($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = ContainerInspection::show($id);
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
      Description : Menyimpan daftar item pada inspeksi kontainer
      Developer : Didin
      Status : Create
    */
    public function showDetail($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = ContainerInspectionDetail::index($id);
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
      Description : Update data pada inspeksi kontainer
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required',
            'checker_id' => 'required'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            ContainerInspection::update($request->all(), $id);
            ContainerInspectionDetail::storeMultiple($request->detail, $id);
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
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            ContainerInspection::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }
}

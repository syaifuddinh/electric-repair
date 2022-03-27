<?php

namespace App\Http\Controllers\Depo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Depo\MovementContainer;
use App\Abstracts\Depo\MovementContainerDetail;
use Response;
use DB;
use Exception;

class MovementContainerController extends Controller
{
    public function index(Request $request)
    {
        $dt = MovementContainer::index();
        $data['message'] = 'OK';
        $data['data'] = $dt;
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $id = MovementContainer::store($request->all());
            MovementContainerDetail::storeMultiple($request->detail, $id);
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
            $dt = MovementContainer::show($id);
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
      Description : Menampilkan daftar perpindahan
      Developer : Didin
      Status : Create
    */
    public function showDetail($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = MovementContainerDetail::index($id);
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
      Description : Update data 
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id)
    {
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            MovementContainer::update($request->all(), $id);
            MovementContainerDetail::storeMultiple($request->detail, $id);
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
            MovementContainer::destroy($id);
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
      Description : Approve data
      Developer : Didin
      Status : Create
    */
    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully approved';
        DB::beginTransaction();
        try {
            MovementContainer::approve($id);
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

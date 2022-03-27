<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Exception;
use App\Abstracts\Setting\ContainerType;

class ContainerTypeController extends Controller
{

    /*
      Date : 25-03-2021
      Description : Menampilkan daftar nama satuan
      Developer : Didin
      Status : Create
    */
    public function index(Request $request)
    {
        $dt = ContainerType::index();
        $data['message'] = 'OK';
        $data['data'] = $dt;
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 25-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
        {
            $request->validate([
                'name' => 'required'
            ]);

            $status_code = 200;
            $msg = 'Data successfully saved';
            DB::beginTransaction();
            try {
                ContainerType::store($request->all());
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
      Date : 25-03-2021
      Description : Menampilkan detail data
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = ContainerType::show($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }


    /*
      Date : 25-03-2021
      Description : Menampilkan update data
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            ContainerType::update($request->all(), $id);
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
      Date : 25-03-2021
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
            ContainerType::destroy($id);
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
        
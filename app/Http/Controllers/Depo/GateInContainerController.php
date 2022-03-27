<?php

namespace App\Http\Controllers\Depo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Depo\GateInContainer;
use Response;
use DB;

class GateInContainerController extends Controller
{
    public function index(Request $request)
    {
        $dt = GateInContainer::index();
        $data['message'] = 'OK';
        $data['data'] = $dt;
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required',
            'container_type_id' => 'required',
            'no_container' => 'required'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $gate_in_container_id = GateInContainer::store($request->all());
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
            $dt = GateInContainer::show($id);
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
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            GateInContainer::update($request->all(), $id);
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
            GateInContainer::destroy($id);
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
            GateInContainer::approve($id);
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

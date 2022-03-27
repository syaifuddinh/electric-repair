<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\UsingItem;
use App\Model\UsingItemDetail;
use App\Abstracts\Inventory\UsingItem AS UI;
use App\Abstracts\Inventory\UsingItemDetail AS UID;
use Carbon\Carbon;
use App\Utils\TransactionCode;
use Response;
use DB;

class UsingItemController extends Controller
{
    public function index()
    {
    }

    public function create()
    {
    }

    
    /*
      Date : 10-03-2020
      Description : Menyimpan penggunaan barang
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
        $request->validate([
            'date_request' => 'required',
            'warehouse_id' => 'required',
        ],[
            'date_request.required' => 'Tanggal pengajuan tidak boleh kosong',
            'warehouse_id.required' => 'Gudang tidak boleh kosong'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            UI::store($request->all());
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 10-03-2020
      Description : Menampilkan detail penggunaan barang
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
        $data['item'] = UI::show($id);
        $data['detail'] = UID::index($id);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    
    /*
      Date : 10-03-2020
      Description : men-setujui penggunaan barang dan mengurangi stok 
                    pada inventori
      Developer : Didin
      Status : Edit
    */
    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            UI::itemOut($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date_request' => 'required',
            'warehouse_id' => 'required',
        ],[
            'date_request.required' => 'Tanggal pengajuan tidak boleh kosong',
            'warehouse_id.required' => 'Gudang tidak boleh kosong'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            UI::update($request->all(), $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            UI::destroy($id);
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

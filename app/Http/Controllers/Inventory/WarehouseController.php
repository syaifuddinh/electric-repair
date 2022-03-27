<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\WarehouseMap;
use DB;

class WarehouseController extends Controller
{
    /*
      Date : 25-03-2021
      Description : Menampilkan daftar nama gudang
      Developer : Didin
      Status : Create
    */
    public function index(Request $request) {
        $dt = DB::table('warehouses');

        if($request->filled('company_id')) {
            $dt->where('warehouses.company_id', $request->company_id);
        }

        $dt = $dt->select('id', 'name')
        ->get();

        $data['message'] = 'OK';
        $data['data'] = $dt;

        return response()->json($data);
    }

    /*
      Date : 25-03-2021
      Description : Menampilkan detail data
      Developer : Didin
      Status : Create
    */
    public function show($id) {
        $w = Warehouse::show($id);
        return response()->json($w);
    }

    /*
      Date : 29-08-2021
      Description : Generate warehouse map
      Developer : Didin
      Status : Create
    */
    public function generateMap(Request $request, $id) {
        $status_code = 200;
        $msg = 'Data successfully generated';
        DB::beginTransaction();
        try {
            WarehouseMap::generate($id, $request->row, $request->column, $request->level);
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
      Date : 29-08-2021
      Description : Menampilkan daftar map
      Developer : Didin
      Status : Create
    */
    public function indexMap($id) {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = WarehouseMap::index($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan daftar map
      Developer : Didin
      Status : Create
    */

    public function mapList($id) {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = WarehouseMap::list($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}

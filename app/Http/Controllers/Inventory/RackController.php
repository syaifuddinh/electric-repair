<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Rack;
use App\Abstracts\Inventory\RackMap;
use DB;

class RackController extends Controller
{
  public function index(Request $request) {
      $dt = DB::table('racks')
      ->join('storage_types', 'storage_type_id', '=', 'storage_types.id');

      if($request->filled('warehouse_id')) {
        $dt->where('racks.warehouse_id', $request->warehouse_id);
      }
      
      $dt = $dt->select(
        'racks.id',
        DB::raw("racks.code as name"),'warehouse_id', 
        DB::raw('capacity_volume - capacity_volume_used AS capacity_volume'), 
        DB::raw('capacity_tonase - capacity_tonase_used AS capacity_tonase'))
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
    public function show($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = Rack::show($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 25-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request) {
        $request->validate([
            'warehouse_id' => 'required',
            'code' => 'required',
            'storage_type_id' => 'required',
            'capacity_tonase' => 'required',
            'capacity_volume' => 'required',
        ],[
            'storage_type_id.required' => 'Type Storage harus diisi'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            Rack::store($request->all());
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
      Date : 25-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id) {
        $request->validate([
            'warehouse_id' => 'required',
            'code' => 'required',
            'storage_type_id' => 'required',
            'capacity_tonase' => 'required',
            'capacity_volume' => 'required',
        ],[
            'storage_type_id.required' => 'Type Storage harus diisi'
        ]);

        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            Rack::update($request->all(), $id);
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
      Date : 25-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function getSuggestionDescending(Request $request) {
        $status_code = 200;
        $msg = 'OK';
        try {
            $rack_id = Rack::getSuggestion($request->warehouse_id, 'DESC');
            $data['data'] = ['rack_id' => $rack_id] ;
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 25-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function setMap($id, $warehouse_map_id) {
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            RackMap::assign($id, $warehouse_map_id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}

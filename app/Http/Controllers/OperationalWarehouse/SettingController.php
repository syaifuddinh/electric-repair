<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Rack;
use App\Model\Warehouse;
use App\Model\Company;
use App\Model\Category;
use App\Model\StorageType;
use App\Abstracts\Rack AS R;
use DB;
use Response;
use Exception;

class SettingController extends Controller
{
  public function rack()
  {
    $data['storage_type']=StorageType::whereRaw('1 = 1')->get();
    return Response::json($data, 200);
  }

  public function detail_rack($id)
  {
    $dt = DB::table('racks as r');
    $dt = $dt->leftJoin('warehouses as w','w.id','r.warehouse_id');
    $dt = $dt->leftJoin('companies as c','c.id','w.company_id');
    $dt = $dt->leftJoin('storage_types as st','st.id','r.storage_type_id');
    $dt = $dt->where('r.id', $id);
    $dt = $dt->selectRaw('r.*,c.name as company,w.name as warehouse,st.name as storage_type')->first();
    return response()->json($dt);
  }

  public function showRackQRCode($id) {
     $resp = R::showQRCodePDF($id);
     $resp->setOption('margin-top', 1);
     $resp->setOption('margin-bottom', 1);
     $resp->setOption('margin-left', 1);
     $resp->setOption('margin-right', 1);
     $resp->setOption('page-width', '5cm');
     $resp->setOption('page-height', '4cm');
     return $resp->stream();
  }

  public function warehouse()
  {
    $warehouses = DB::table('warehouses')
    ->whereIsActive(1)
    ->select('id', 'code', 'name')
    ->get();
    
    return Response::json($warehouses, 200, [], JSON_NUMERIC_CHECK);
  }

  public function delete_rack($id)
  {
    DB::beginTransaction();
    Rack::find($id)->delete();
    DB::commit();
  }
  public function delete_warehouse($id)
  {
    DB::beginTransaction();
    Warehouse::find($id)->delete();
    DB::commit();
  }

  public function store_warehouse(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'company_id' => 'required',
        'code' => 'required|unique:warehouses,code,'.$request->id,
        'name' => 'required',
        'capacity_tonase' => 'required',
        'capacity_volume' => 'required',
      ]);

      Warehouse::find($request->id)->update([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'luas_gudang' => $request->luas_gudang,
        'luas_lahan' => $request->luas_lahan,
        'luas_bangunan' => $request->luas_bangunan
      ]);
    } else {
      $request->validate([
        'company_id' => 'required',
        'code' => 'required|unique:warehouses,code',
        'name' => 'required',
        'capacity_tonase' => 'required',
        'capacity_volume' => 'required',
      ]);

      $city_id = Company::find($request->company_id)->city_id;
      $wh = Warehouse::create([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'name' => $request->name,
        'city_id' => $city_id,
        'warehouse_type_id' => 1,
        'address' => $request->address,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'luas_gudang' => $request->luas_gudang,
        'luas_lahan' => $request->luas_lahan,
        'luas_bangunan' => $request->luas_bangunan
      ]);

      Rack::create([
        'warehouse_id' => $wh->id,
        'code' => 'Handling Area',
        'barcode' => '',
        'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
        'capacity_volume' => 1000000000,
        'capacity_tonase' => 1000000000,
      ]);

      Rack::create([
        'warehouse_id' => $wh->id,
        'code' => 'Picking Area',
        'barcode' => '',
        'storage_type_id' => StorageType::where('is_picking_area', 1)->first()->id,
        'capacity_volume' => 10000000000,
        'capacity_tonase' => 10000000000,
      ]);
    }
    DB::commit();

    return response()->json(['message' => 'Data successfully saved']);
  }

  public function category_pallet_list()
  {
    $data['category']=DB::table('categories')->whereRaw('is_pallet = 1')->get();
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function store_pallet_category(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'code' => 'required|unique:categories,code,'.$request->id,
        'name' => 'required'
      ]);
      $input = [];
      $input['name'] = $request->name;
      $input['parent_id'] = $request->parent_id;
      $input['description'] = $request->description;
      $input['code'] = $request->code;
      Category::find($request->id)->update($input);
    } else {
      $request->validate([
        'code' => 'required|unique:categories,code',
        'name' => 'required'
      ]);
      $input=$request->all();
      Category::create($input);
    }
    DB::commit();

    return Response::json(null,200);
  }

  public function store_storage_type(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'name' => 'required'
      ]);
      $input=$request->except('id');
      StorageType::find($request->id)->update($input);
    } else {
      $request->validate([
        'name' => 'required'
      ]);
      $input=$request->all();
      StorageType::create($input);
    }
    DB::commit();

    return Response::json(null,200);
  }
}

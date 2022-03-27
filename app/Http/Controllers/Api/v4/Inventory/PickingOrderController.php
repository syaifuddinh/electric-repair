<?php

namespace App\Http\Controllers\Api\v4\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use App\Model\Picking;
use Excel;
use Carbon\Carbon;
use DataTables;

class PickingOrderController extends Controller
{
  public function picking_order_datatable(Request $request)
  {
    $item = DB::table('pickings as p');
    $item = $item->leftJoin('warehouses as w','w.id','p.warehouse_id');
    $item = $item->leftJoin('companies as c','c.id','p.company_id');
    $item = $item->leftJoin('contacts as cu','cu.id','p.customer_id');
    $item = $item->leftJoin('contacts as st','st.id','p.staff_id');
    $item = $item->selectRaw('p.*,w.name as warehouse,c.name as company,cu.name as customer,st.name as staff');
    return DataTables::of($item)->toJson();
  }
  public function create()
  {
    $data['staff']=DB::table('contacts')->selectRaw('id,name')->where('is_pegawai', 1)->get();
    $data['company']=DB::table('companies')->selectRaw('id,name')->get();
    $data['customer']=DB::table('contacts')->selectRaw('id,name')->where('is_pelanggan', 1)->get();
    $data['warehouse']=DB::table('warehouses')->selectRaw('id,name,company_id')->get();
    return response()->json($data);
  }
  public function store(Request $request)
  {
    $request->validate([
      'company_id' => 'required',
      'warehouse_id' => 'required',
      'customer_id' => 'required',
      'staff_id' => 'required',
      'date_transaction' => 'required',
    ]);
    DB::beginTransaction();
    $insertGetId=DB::table('pickings')->insertGetId([
      'company_id' => $request->company_id,
      'customer_id' => $request->customer_id,
      'warehouse_id' => $request->warehouse_id,
      'staff_id' => $request->staff_id,
      'date_transaction' => Carbon::parse($request->date_transaction),
      'description' => $request->description
    ]);
    DB::commit();
    return response()->json(['message' => 'OK','id' => $insertGetId]);
  }
  public function show($id)
  {
    $data['item']=Picking::with('warehouse','company','customer','staff')->where('id', $id)->first();
    $dt = DB::table('picking_details as pd');
    $dt = $dt->leftJoin('categories as c','c.id','pd.category_id');
    $dt = $dt->leftJoin('items as i','i.id','pd.item_id');
    $dt = $dt->leftJoin('racks as r','r.id','pd.rack_id');
    $dt = $dt->where('pd.header_id', $id);
    $dt = $dt->selectRaw('pd.*,c.name as category,i.name as item_name,i.code as item_code,r.code as bin');
    $dt = $dt->orderBy('pd.id');
    $dt = $dt->get();
    $data['detail']=$dt;
    return response()->json($data);
  }
  public function realisation(Request $request, $id)
  {
    DB::beginTransaction();
    foreach ($request->detail as $key => $value) {
      DB::table('picking_details')->where('id', $value['id'])->update([
        'qty_delivered' => $value['qty_realisation']
      ]);
    }
    DB::table('pickings')->where('id', $id)->update([
      'status' => 2
    ]);
    DB::commit();
    return response()->json(['message' => 'OK']);
  }
  public function cancel_realisation($id)
  {
    DB::beginTransaction();
    DB::table('pickings')->where('id', $id)->update([
      'status' => 1
    ]);
    DB::update("UPDATE picking_details SET qty_delivered = 0 WHERE header_id = $id");
    DB::commit();
    return response()->json(['message' => 'OK']);
  }
  public function posting($id)
  {
    DB::table('pickings')->where('id', $id)->update([
      'status' => 3
    ]);
    return response()->json(['message' => 'OK']);
  }
  public function update_qty(Request $request)
  {
    DB::beginTransaction();
    DB::table('picking_details')->where('id', $request->id)->update([
      'qty_delivered' => $request->qty_delivered
    ]);
    DB::commit();
    return response()->json(['message' => 'OK']);
  }
}

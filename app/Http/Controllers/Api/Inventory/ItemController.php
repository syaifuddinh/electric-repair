<?php

namespace App\Http\Controllers\Api\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Rack;
use App\Model\Category;
use App\Model\Account;
use Response;
use DB;
use Carbon\Carbon;
use Excel;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Warehouse ID and Customer ID is mandatory
        $item_warehouse = Item::with('customer', 'sender', 'receiver', 'piece')
        ->leftJoin('warehouse_stock_details', 'item_id', 'items.id')
        ->leftJoin('racks', 'rack_id', 'racks.id')
        ->where('customer_id', $request->customer_id);

        // Apakah item dari rak penyimpanan atau handling area atau picking area
        if(isset($request->is_handling_area)) {
            $rack = Rack::leftJoin('storage_types', 'storage_types.id', 'storage_type_id')->where('warehouse_id', $request->warehouse_id)->where('is_handling_area', 1)->select('racks.id')->first();
            $rack_id = $rack->id;
        }
        else if(isset($request->is_picking_area)) {
            $rack = Rack::leftJoin('storage_types', 'storage_types.id', 'storage_type_id')->where('warehouse_id', $request->warehouse_id)->where('is_picking_area', 1)->select('racks.id')->first();
            $rack_id = $rack->id;
        }
        else {
            $rack_id = $request->rack_id;
        }

        $data['item_warehouse'] = $item_warehouse->where('rack_id', $rack_id)->selectRaw('items.*, warehouse_stock_details.qty')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    public function general_item(Request $request)
    {
        // Warehouse ID and Customer ID is mandatory
        $item_warehouse = Item::with('customer', 'sender', 'receiver', 'piece')
        ->leftJoin('warehouse_stocks', 'item_id', 'items.id')
        ->where('customer_id', $request->customer_id)
        ->where('warehouse_id', $request->warehouse_id);

        // Apakah item dari rak penyimpanan atau handling area atau picking area
        $data['item_warehouse'] = $item_warehouse->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cekStok(Request $request)
    {
        $rack_id = $request->rack_id;
        $rack_id = $request->rack_id;
    }


    public function cek_stok_warehouse(Request $request) {
      $item = DB::table('warehouse_stock_details')->where('item_id', $request->item_id)->where('no_surat_jalan', $request->no_surat_jalan);
      if(empty($request->is_picking_area) && empty($request->is_handling_area)) {
        $item = $item->where('rack_id', $request->rack_id);
      }
      else {
        if(isset($request->is_picking_area)) {
            $rack = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', 'storage_types.id')
      ->where('is_picking_area', 1)
      ->where('warehouse_id', $request->warehouse_id)
      ->select('racks.id')
      ->first();
        $item = $item->where('rack_id', $rack->id);

        }
        else if(isset($request->is_handling_area)) {
            $rack = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', 'storage_types.id')
              ->where('is_handling_area', 1)
              ->where('warehouse_id', $request->warehouse_id)
              ->select('racks.id')
              ->first();
                $item = $item->where('rack_id', $rack->id);
        }
      }
      $result = $item->first();
      $data['stok'] = $result != null ? $result->qty : 0;
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function rack(Request $request)
    {
        // Warehouse ID
        $racks = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('warehouse_id', $request->warehouse_id);

        if(isset($request->is_picking_area)) {

          $racks = $racks->where('is_picking_area', 1);
        }
        else {

          $racks = $racks->where('is_picking_area', 0);
        }

        if(isset($request->is_handling_area)) {
          $racks = $racks->where('is_handling_area', 1);
        }
        else {
          $racks = $racks->where('is_handling_area', 0);
        }

        if(isset($request->no_surat_jalan)) {
          $racks = $racks->join('warehouse_stock_details', 'rack_id', 'racks.id')->where('no_surat_jalan', $request->no_surat_jalan);
        }

        $data['rack'] = $racks->select('racks.*')->groupBy('racks.id')->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function surat_jalan(Request $request)
    {
        // Warehouse ID
        $surat_jalan = DB::table('warehouse_stock_details')->join('racks', 'rack_id', 'racks.id');
        // Jika filter berdasarkan gudang
        if(isset($request->warehouse_id)) {
            $surat_jalan = $surat_jalan->where('warehouse_id', $request->warehouse_id);
        }
        if(isset($request->rack_id)) {
            $surat_jalan = $surat_jalan->where('rack_id', $request->rack_id);
        }
        if(isset($request->customer_id)) {
            $surat_jalan = $surat_jalan->where('customer_id', $request->customer_id);
        }
        $data['surat_jalan'] = $surat_jalan->whereRaw('no_surat_jalan IS NOT NULL')->select('no_surat_jalan')->groupBy('no_surat_jalan')->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['category']=Category::whereRaw("parent_id is not null and ban_master = 0")->get();
      $data['account']=Account::with('parent')->where('is_base',0)->get();
      $data['piece']=DB::table('pieces')->select('id','name')->get();
      $data['supplier']=DB::table('contacts')->whereRaw('vendor_status_approve = 2 and (is_supplier = 1 or is_vendor = 1)')->select('id','name')->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'code' => 'required|unique:items,code',
            'name' => 'required',
            'category_id' => 'required',
            'piece_id' => 'required',
            'is_active' => 'required',
            'is_stock' => 'required',
            'account_id' => 'required'
        ];

        $customMessages = [
            'piece_id.required' => 'Satuan harus diisi!',
            'account_id.required' => 'Default akun harus diisi!'
        ];

        $this->validate($request, $rules, $customMessages);

      DB::beginTransaction();
      Item::create([
        'category_id' => $request->category_id,
        'code' => $request->code,
        'name' => $request->name,
        'initial_cost' => $request->initial_cost,
        'part_number' => $request->part_number,
        'qrcode' => $request->qrcode,
        'barcode' => $request->barcode,
        'description' => $request->description,
        'account_id' => $request->account_id,
        'is_stock' => $request->is_stock,
        'is_active' => $request->is_active,
        'main_supplier_id' => $request->main_supplier_id,
        'minimal_stock' => $request->minimal_stock,
        'piece_id' => $request->piece_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['category']=Category::whereRaw("parent_id is not null and ban_master = 0")->get();
      $data['account']=Account::with('parent')->where('is_base',0)->get();
      $data['item']=Item::find($id);
      $data['piece']=DB::table('pieces')->select('id','name')->get();
      $data['supplier']=DB::table('contacts')->whereRaw('vendor_status_approve = 2 and (is_supplier = 1 or is_vendor = 1)')->select('id','name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $request->validate([
        'code' => 'required|unique:items,code,'.$id,
        'name' => 'required',
        'category_id' => 'required',
      ]);

      DB::beginTransaction();
      Item::find($id)->update([
        'category_id' => $request->category_id,
        'code' => $request->code,
        'name' => $request->name,
        'initial_cost' => $request->initial_cost,
        'part_number' => $request->part_number,
        'qrcode' => $request->qrcode,
        'barcode' => $request->barcode,
        'description' => $request->description,
        'account_id' => $request->account_id,
        'is_stock' => $request->is_stock,
        'is_active' => $request->is_active,
        'main_supplier_id' => $request->main_supplier_id,
        'minimal_stock' => $request->minimal_stock,
        'piece_id' => $request->piece_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      Item::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function import_picking(Request $request,$id)
    {
      // dd($request);
      $errors=[];
      $count=0;
      Excel::load($request->file('file'),function($reader)use($id,&$count,&$errors){
        $data = $reader->toArray();

        //field
        $parent=0;
        $childno=1;
        $childname=2;
        $slocbin=3;
        $lot=4;
        $um=5;
        $qty=6;
        $dschedule=7;
        $timestaging=8;
        $timefinish=9;
        $delivered=10;
        DB::beginTransaction();
        for ($i=0; $i < count($data); $i++) {
          if($i==0) continue;
          $value = $data[$i];
          $cat=DB::table('categories')->where('name', $value[$parent])->selectRaw('id')->first();
          if(!$cat){
            array_push($errors,'Parents Name with keyword "'.$value[$parent].'" was not found!');
            continue;
          }
          $item=DB::table('items')->where('code', $value[$childno])->selectRaw('id')->first();
          if(!$item){
            array_push($errors,'Child No with keyword "'.$value[$childno].'" was not found!');
            continue;
          }
          $bin=DB::table('racks')->where('code', $value[$slocbin])->selectRaw('id')->first();
          if(!$bin){
            array_push($errors,'Sloc with keyword "'.$value[$slocbin].'" was not found!');
            continue;
          }
          DB::table('picking_details')->insert([
            'header_id' => $id,
            'category_id' => $cat->id,
            'item_id' => $item->id,
            'rack_id' => $bin->id,
            'qty' => $value[$qty],
            'lot_no' => $value[$lot],
            'um' => $value[$um],
            'delivery_schedule' => $value[$dschedule]?Carbon::parse($value[$dschedule]):null,
            'time_staging' => $value[$timestaging]?Carbon::parse($value[$timestaging]):null,
            'time_finish' => $value[$timefinish]?Carbon::parse($value[$timefinish]):null,
            'qty_delivered' => $value[$delivered]??0,
          ]);
          $count++;
        }
        DB::commit();
      })->get();
      return response()->json(['message' => 'OK','errors' => $errors,'total_imported' => $count]);
    }
}

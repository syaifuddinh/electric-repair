<?php

namespace App\Http\Controllers\Api\v4\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Rack;
use App\Model\Category;
use App\Model\Account;
use Response;
use DB;
use Carbon\Carbon;
use App\Abstracts\Inventory\Item AS IT;

class ItemController extends Controller
{

  /*
      Date : 16-04-2020
      Description : Menampilkan detail stok customer
      Developer : Didin
      Status : Create
  */
  public function showStock(Request $request, $customer_id, $item_id)
  {
    

    $item = DB::table('items')
    ->whereId($item_id)
    ->first();

    if($item == null) {
        return Response::json(['status' => 'ERROR', 'message' => 'Barang tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    $stock = DB::table('stock_transactions_report')
    ->join('stock_transactions', 'stock_transactions.id', 'stock_transactions_report.header_id')
    ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'stock_transactions.warehouse_receipt_detail_id')
    ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
    ->join('items', 'items.id', 'stock_transactions_report.item_id');

    if($request->filled('customer_id')) {
        $stock->where('warehouse_receipts.customer_id', $request->customer_id);
    }

    if($request->filled('warehouse_id')) {
        $stock->where('warehouse_receipts.warehouse_id', $request->customer_id);
    }

    $stock = $stock
    ->selectRaw('
      items.name AS item_name, 
      items.id AS item_id, 
      items.long, 
      items.wide, 
      items.height, 
      SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar) AS stock
    ')
    ->first();

    $resp['status'] = 'OK';
    $resp['message'] = '';
    $resp['data'] = null;


    return Response::json($resp, 200, [], JSON_NUMERIC_CHECK);
  }
    
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

        $data['rack'] = $racks->select('racks.id', 'racks.code', 'racks.warehouse_id')->groupBy('racks.id')->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function surat_jalan(Request $request)
    {
        // Warehouse ID
        $warehouse_receipt = DB::table('warehouse_stock_details')
        ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_stock_details.warehouse_receipt_id')
        ->join('racks', 'warehouse_stock_details.rack_id', 'racks.id');
        // Jika filter berdasarkan gudang
        if(isset($request->warehouse_id)) {
            $warehouse_receipt = $warehouse_receipt->where('warehouse_receipts.warehouse_id', $request->warehouse_id);
        }
        if(isset($request->rack_id)) {
            $warehouse_receipt = $warehouse_receipt->where('warehouse_stock_details.rack_id', $request->rack_id);
        }
        if(isset($request->customer_id)) {
            $warehouse_receipt = $warehouse_receipt->where('warehouse_receipts.customer_id', $request->customer_id);
        }
  
        if($request->filled('keyword')){
            $warehouse_receipt->where('warehouse_receipts.code', 'LIKE', DB::raw("'%{$request->keyword}%'"));
        }


        $data['warehouse_receipt'] = $warehouse_receipt
        ->whereNotNull('warehouse_stock_details.warehouse_receipt_id')
        ->select('warehouse_receipts.id', 'warehouse_receipts.code')
        ->groupBy('warehouse_receipts.id')
        ->get();
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

    /*
      Date : 19-04-2021
      Description : Menampilkan detail berdasarkan barcode
      Developer : Didin
      Status : Create
    */
    public function showByBarcode($barcode) {
        $dt = IT::showByBarcode($barcode);
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return response()->json($data);
    }
}

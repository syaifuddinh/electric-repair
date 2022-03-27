<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Rack;
use App\Model\Category;
use App\Model\Account;
use App\Model\ItemPicture;
use App\Abstracts\Setting\Math;
use App\Abstracts\Inventory\Item AS IT;
use Response;
use DB;
use Carbon\Carbon;
use DataTables;

class ItemController extends Controller
{
    public function datatable(Request $request)
    {
        $wr="1=1";
        if ($request->category_id) {
          $wr.=" and items.category_id = $request->category_id";
        }
        if ($request->start_price) {
          $wr.=" and items.harga_beli >= $request->start_price";
        }
        if ($request->end_price) {
          $wr.=" and items.harga_beli <= $request->end_price";
        }

        $wr2 = '';
        if($request->filled('warehouse_id')) {
            $wr2 .= 'AND warehouse_id = ' . $request->warehouse_id;
        }

        $stockTransaction = DB::raw("(SELECT SUM(qty_masuk - qty_keluar) AS qty, item_id FROM stock_transactions WHERE warehouse_receipt_detail_id IS NULL $wr2 GROUP BY item_id) AS stock_transactions");


        $item=DB::table('items')
        ->leftJoin('categories','categories.id','items.category_id')
        ->leftJoin('categories as parents','parents.id','categories.parent_id')
        ->leftJoin($stockTransaction, 'stock_transactions.item_id', 'items.id')
        ->whereRaw($wr)
        ->whereRaw('customer_id is null')
        ->selectRaw('
          items.id,
          items.code,
          items.name,
          items.part_number,
          items.harga_beli,
          items.description,
          COALESCE(stock_transactions.qty, 0) AS qty,
          categories.name as category,
          parents.name as parent
        ');

        if($request->default_rack_id) {
            $item = $item->where('items.default_rack_id', $request->default_rack_id);
        }

        $quotation_id = null;
        if($request->show_quotation_price) {
            if($request->quotation_id) {
                $quotation_id = $request->quotation_id;
                $quotation_items = DB::raw("(SELECT item_id, price FROM quotation_items WHERE quotation_id = $quotation_id GROUP BY item_id) AS quotation_items");
                $item->leftJoin($quotation_items, 'quotation_items.item_id', 'items.id');
            }
        }

        if(!$quotation_id) {
            $item->addSelect(['items.harga_jual']);

            if($request->harga_jual_greater_than && is_int((int)$request->harga_jual_greater_than)) {
                $item->where('items.harga_jual', '>=', $request->harga_jual_greater_than);
            }
        } else {
            $item->addSelect([DB::raw('COALESCE(quotation_items.price, items.harga_jual) AS harga_jual')]);
            if($request->harga_jual_greater_than && is_int((int)$request->harga_jual_greater_than)) {
                $item->where(DB::raw('COALESCE(quotation_items.price, items.harga_jual)'), '>=', $request->harga_jual_greater_than);
            }
        }

        if($request->is_container_part == 1) {
            $item = $item->where(function($query){
                $query->where('parents.is_container_part', 1);
                $query->orWhere('categories.is_container_part', 1);
            });
        }

        if($request->is_merchandise == 1) {
            $item->where('items.is_merchandise', 1);
        }

        if($request->is_service == 1) {
            $item->where('items.is_service', 1);
        }

        if($request->is_container_yard == 1) {
            $item = $item->where(function($query){
                $query->where('parents.is_container_yard', 1);
                $query->orWhere('categories.is_container_yard', 1);
            });
        }

        if($request->is_pallet == 1) {
            $item = $item->where(function($query){
                $query->where('parents.is_pallet', 1);
                $query->orWhere('categories.is_pallet', 1);
            });
        }

        return DataTables::of($item)->make(true);
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
      $item = DB::table('warehouse_stock_details')->where('item_id', $request->item_id)->where('warehouse_receipt_id', $request->warehouse_receipt_id);
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
      $data['stok'] = $item->first() != null ? $item->first()->qty : 0;
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

        if(isset($request->warehouse_receipt_id)) {
          $racks = $racks->join('warehouse_stock_details', 'rack_id', 'racks.id')->where('warehouse_stock_details.warehouse_receipt_id', $request->warehouse_receipt_id);
        }

        $data['rack'] = $racks->select('racks.*')->groupBy('racks.id')->get();
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
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code')->get();
      $data['supplier']=DB::table('contacts')->whereRaw('is_supplier = 1 or is_vendor = 1')->select('id','name')->get();

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
            'is_stock' => 'required',
        ];

        $this->validate($request, $rules);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            IT::store($request->all());
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = IT::show($id);
            $data = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $data['message'] = $msg;
        }

        return Response::json($data, $status_code);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['category']=DB::table('categories')->whereRaw("parent_id is not null and ban_master = 0")->selectRaw('id,parent_id,code,name,is_tire,is_asset,is_jasa,is_ban_luar,is_ban_dalam,is_marset,ban_master,is_pallet')->get();
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code')->get();
      $data['piece']=DB::table('pieces')->select('id','name')->get();
      $data['supplier']=DB::table('contacts')->whereRaw('is_supplier = 1 or is_vendor = 1')->select('id','name')->get();
      $data['item']=Item::find($id);
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
            'category_id' => 'required'
        ]);

        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            IT::update($request->all(), $id);
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
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
      DB::beginTransaction();
      Item::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function uploadPicture(Request $request, $id)
    {
      $request->validate([
        'file' => 'required|mimes:jpeg,jpg,png'
      ]);
      $file=$request->file('file');
      $filename="ITEM_".$id."_".date('Ymd_His').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
      DB::beginTransaction();
      ItemPicture::create([
        'item_id' => $id,
        'file_path' => 'files/item_pictures/'.$filename,
      ]);
      $file->move(public_path('files/item_pictures'), $filename);
      DB::commit();

      return response()->json(['message' => 'OK']);
    }

    public function get_pictures($id)
    {
      $dt = DB::table('item_pictures');
      $dt = $dt->where('item_id', $id);
      $dt = $dt->orderBy('id','desc')->get();
      return response()->json(['data' => $dt]);
    }
}

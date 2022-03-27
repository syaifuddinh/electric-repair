<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Carbon\Carbon;

class ItemController extends Controller
{

  /*
      Date : 16-04-2020
      Description : Menampilkan detail stok customer
      Developer : Didin
      Status : Create
  */
  public function showStock(Request $request, $item_id)
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
    ->join('items', 'items.id', 'stock_transactions_report.item_id')
    ->where('items.id', $item_id);

    if($request->filled('customer_id')) {
        $stock->where('warehouse_receipts.customer_id', $request->customer_id);
    }

    if($request->filled('warehouse_id')) {
        $stock->where('stock_transactions_report.warehouse_id', $request->warehouse_id);
    }

    DB::table('items')
    ->update([
        'volume' => DB::raw('`long` * wide * height / 1000000'),
    ]);

    $stock = $stock
    ->selectRaw('
      items.name AS item_name, 
      items.id AS item_id, 
      items.long, 
      items.wide, 
      items.height, 
      items.volume, 
      IFNULL(stock_transactions_report.qty_masuk, 0) AS qty_masuk, 
      IFNULL(stock_transactions_report.qty_keluar, 0) AS qty_keluar, 
      warehouse_receipts.stripping_done, 
      warehouse_receipt_details.imposition, 
      IF(warehouse_receipt_details.imposition = 1, "Volume based", IF(warehouse_receipt_details.imposition = 2, "Tonase based", IF( warehouse_receipt_details.imposition = 3, "Item Based", IF(warehouse_receipt_details.imposition = 4, "Wholesale based", null) ))) imposition_name, 
      IFNULL(SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar), 0) AS stock
    ')
    ->first();

    $resp['status'] = 'OK';
    $resp['message'] = '';
    $resp['data'] = (object)$stock;


    return Response::json($resp, 200, [], JSON_NUMERIC_CHECK);
  }
    
}

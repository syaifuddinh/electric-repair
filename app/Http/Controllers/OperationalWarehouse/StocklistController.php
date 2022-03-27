<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WarehouseStock;
use App\Model\WarehouseStockDetail;
use DB;
use Response;
use Carbon\Carbon;
use Excel;

class StocklistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function excel(Request $request)
    {
        return Excel::create('Stocklist', function($excel) use($request) {

            $excel->sheet('Stocklist', function($sheet)  use($request){

                $item = WarehouseStockDetail::fetch_stocklist($request);
                $item->leftJoin('pieces', 'items.piece_id', 'pieces.id');
                $item->addSelect('items.long', 'items.wide', 'items.height', 'items.tonase', 'city_to', 'pieces.name AS piece_name', 'warehouse_receipts.description');
                $sheet->loadView('export.stocklist')->withUnits($item->get());

            });

        })->download('xls');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['warehouse']=DB::table('warehouses')->get();
      $data['rack']=DB::table('racks')->get();
      $data['item']=DB::table('items')->selectRaw('id,code,name,barcode')->get();

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        WarehouseStockDetail::findOrFail($id);

        $item = WarehouseStockDetail::join('racks', 'rack_id', 'racks.id')
        ->join('items', 'item_id', 'items.id')
        ->leftJoin('pieces', 'items.piece_id', 'pieces.id')
        ->leftJoin('warehouse_receipts', 'warehouse_receipts.code', 'warehouse_stock_details.no_surat_jalan')
        ->join('warehouses', 'racks.warehouse_id', 'warehouses.id')
        ->join('contacts', 'warehouse_receipts.customer_id', 'contacts.id')
        ->whereRaw('warehouse_stock_details.customer_id IS NOT NULL AND (qty IS NOT NULL OR qty > 0) AND no_surat_jalan IS NOT NULL')
        ->where('warehouse_stock_details.id', $id);

        $item = $item->selectRaw('warehouse_stock_details.id, 
          SUM(qty) AS qty, 
          items.name, 
          items.code,  
          items.long,  
          items.wide,  
          items.height,  
          items.tonase,  
          pieces.name as piece_name,
          warehouse_stock_details.no_surat_jalan, 
          warehouse_receipts.sender, 
          warehouse_receipts.city_to, 
          warehouse_receipts.description, 
          warehouse_receipts.receiver, 
          contacts.name AS customer_name, 
          contacts.id AS customer_id, 
          warehouses.name AS warehouse_name, 
          warehouses.id AS warehouse_id, 
          warehouse_receipts.receive_date')
          ->groupBy('racks.warehouse_id', 'warehouse_receipts.id', 'item_id', 'warehouse_receipts.customer_id')
          ->first();

          return Response::json($item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

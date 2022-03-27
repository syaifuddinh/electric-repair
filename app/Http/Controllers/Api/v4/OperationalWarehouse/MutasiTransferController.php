<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Rack;
use App\Model\ItemMigration;
use App\Model\ItemMigrationDetail;
use App\Model\StockTransaction;
use App\Model\WarehouseStock;
use App\Model\WarehouseStockDetail;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class MutasiTransferController extends Controller
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
      if(!isset($request->warehouse_from_id)) {
        return Response::json(["message" => "Gudang asal tidak boleh kosong"], 422);
      }
      if(!isset($request->warehouse_to_id)) {
        return Response::json(["message" => "Gudang tujuan tidak boleh kosong"], 422);
      }
      if(!isset($request->rack_from_id)) {
        return Response::json(["message" => "Rak asal tidak boleh kosong"], 422);
      } else {
          if( Rack::find($request->rack_from_id) == null ) {
              return Response::json(["message" => "ID Rak asal tidak ditemukan"], 422);
          }
      }
      if(!isset($request->rack_to_id)) {
        return Response::json(["message" => "Rak tujuan tidak boleh kosong"], 422);
      } else {
          if( Rack::find($request->rack_to_id) == null ) {
              return Response::json(["message" => "ID Rak tujuan tidak ditemukan"], 422);
          }
      }
      if(!isset($request->date_transaction)) {
        return Response::json(["message" => "Tanggal transaksi tidak boleh kosong"], 422);
      }
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'itemMigration');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=ItemMigration::create([
        'warehouse_from_id' => $request->warehouse_from_id,
        'warehouse_to_id' => $request->warehouse_to_id,
        'rack_from_id' => $request->rack_from_id,
        'rack_to_id' => $request->rack_to_id,
        'date_transaction' => Carbon::parse($request->date_transaction),
        'description' => $request->description,
        'create_by' => auth()->id(),
        'code' => $trx_code
      ]);


      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }

        if(!array_key_exists('warehouse_receipt_detail_id', $value) || $value['warehouse_receipt_detail_id'] == null) {    
            return Response::json(['message' => 'ID Detail penerimaan barang tidak boleh kosong'], 422);
        } else {
            $warehouse_receipt_detail = DB::table('warehouse_receipt_details')
            ->whereId($value['warehouse_receipt_detail_id']) 
            ->count('id');

            if($warehouse_receipt_detail < 1) {            
                return Response::json(['message' => 'ID Detail penerimaan barang tidak ditemukan'], 422);
            }
        }

        if(!array_key_exists('item_id', $value) || $value['item_id'] == null) {    
            return Response::json(['message' => 'ID Barang tidak boleh kosong'], 422);
        } else {
            $item = DB::table('items')
            ->whereId($value['item_id']) 
            ->count('id');

            if($item < 1) {            
                return Response::json(['message' => 'ID Barang tidak ditemukan'], 422);
            }            
        }

        $warehouse_receipt_detail = DB::table('warehouse_receipt_details')
        ->whereId($value['warehouse_receipt_detail_id'])
        ->select('header_id')
        ->first();

        $w = WarehouseStockDetail::whereRackId($request->rack_from_id)->whereWarehouseReceiptId($warehouse_receipt_detail->header_id)->whereItemId($value['item_id'])->first();
        if($w == null) {
            $i = Item::find($value['item_id']);
            return Response::json([
                "message" => 'Stok ' . $i->name . ' tidak mencukupi',
                'item' => [
                    'name' => $i->name,
                    'qty_stock' => 0,
                    'qty_dikeluarkan' => $value['qty']
                ]
            ], 422);
        } else {
            if($w->qty < $value['qty']) {
                $i = Item::find($value['item_id']);
                return Response::json([
                    "message" => 'Stok ' . $i->name . ' tidak mencukupi',
                    'item' => [
                        'name' => $i->name,
                        'qty_stock' => $w->qty,
                        'qty_dikeluarkan' => $value['qty']
                    ]
                ], 422);
            }
        }


        ItemMigrationDetail::create([
          'header_id' => $i->id,
          'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'],
          'item_id' => $value['item_id'],
          'qty' => $value['qty'] ?? 0,
        ]);
      }
      DB::commit();

      return Response::json(['message' => 'Transaksi mutasi transfer berhasil di-input'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $i = ItemMigration::find($id);
      if($i == null) {
        return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 500);
      }
      else {
        if($i->warehouse_from_id == $i->warehouse_to_id) {
            
          return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 422);
        }
      }
      $data['item']=ItemMigration::with('warehouse_from:id,name','warehouse_to:id,name','rack_from:id,code','rack_to:id,code')->where('id', $id)->selectRaw("
        id, 
        no_surat_jalan, 
        warehouse_from_id, 
        warehouse_to_id, 
        rack_from_id, 
        rack_to_id, 
        code, 
        date_transaction, 
        status,
        IF(status = 1, \"Pengajuan\", IF(status = 2, \"Item Out (On Transit)\", \"Item Receipt (Done)\")) AS status_name,
        description
      ")->first();
      $rack_from_id = $data['item']->rack_from_id;
      $rack_to_id = $data['item']->rack_to_id;
      $data['detail']=DB::table('item_migration_details')
      ->leftJoin('items','items.id','item_migration_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'item_migration_details.warehouse_receipt_detail_id')
      ->join('warehouse_receipts', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
      ->where('item_migration_details.header_id', $id)
      ->selectRaw('
      item_migration_details.id,
      item_migration_details.qty,
      items.name as item_name,
      items.barcode,
      items.code as item_code,
      warehouse_receipts.code AS warehouse_receipt_code,
      categories.name as category_name,
      (SELECT IFNULL(SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar), 0) FROM stock_transactions_report JOIN stock_transactions ON stock_transactions.id = stock_transactions_report.header_id WHERE warehouse_receipt_detail_id = item_migration_details.warehouse_receipt_detail_id AND stock_transactions_report.rack_id = ' . $rack_from_id . ') AS stok_asal,
      (SELECT IFNULL(SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar), 0) FROM stock_transactions_report JOIN stock_transactions ON stock_transactions.id = stock_transactions_report.header_id WHERE warehouse_receipt_detail_id = item_migration_details.warehouse_receipt_detail_id AND stock_transactions_report.rack_id = ' . $rack_to_id . ') AS stok_tujuan
      ')->get();
      return Response::json($data,200);
    }

    public function item_out($id)
    {
      DB::beginTransaction();
      $i = ItemMigration::find($id);
      if($i == null) {
        return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 500);
      }
      else {
        if($i->warehouse_from_id == $i->warehouse_to_id) {
            
          return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 422);
        }

        if($i->status==2) {
          return Response::json(['message' => 'Item transaksi tidak dapat dikeluarkan, karena sudah disetujui'], 422);          
        }
        else if($i->status==3) {
          return Response::json(['message' => 'Item transaksi tidak dapat dikeluarkan, karena item sudah diterima di rak penyimpanan tujuan'], 422);          
        }

      }
      $i->update([
        'status' => 2,
        'approve_by' => auth()->id(),
        'date_approve' => Carbon::now()
      ]);

      $detail=DB::table('item_migration_details')->where('header_id', $id)->get();
      foreach ($detail as $key => $value) {
        StockTransaction::create([
          'warehouse_id' => $i->warehouse_from_id,
          'rack_id' => $i->rack_from_id,
          'item_id' => $value->item_id,
          'type_transaction_id' => 38,
          'code' => $i->code,
          'date_transaction' => Carbon::now(),
          'description' => 'Pengeluaran Barang pada Mutasi - '.$i->code,
          'qty_keluar' => $value->qty,
        ]);
        sleep(1);
        WarehouseStock::whereRaw("warehouse_id = $i->warehouse_from_id and item_id = $value->item_id")->update([
          'transit' => DB::raw("transit+$value->qty")
        ]);
      }
      DB::commit();

      return Response::json(['message' => 'Item transaksi putaway berhasil dikeluarkan dari rak penyimpanan asal'],200);
    }
    public function item_in($id)
    {
      DB::beginTransaction();
      $i = ItemMigration::find($id);
      if($i == null) {
        return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 500);
      }
      else {
        if($i->warehouse_from_id == $i->warehouse_to_id) {
            
          return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 422);
        }
        else {
          if($i->status == 1) {

            return Response::json(['message' => 'Item transaksi tidak dapat dimasukkan ke rak penyimpanan tujuan, karena transaksi masih dalam pengajuan'], 422);
          }
          else if($i->status == 3) {

            return Response::json(['message' => 'Item transaksi tidak dapat dimasukkan ke rak penyimpanan tujuan, karena item sudah diterima'], 422);
          }
        }
      }
      $i->update([
        'status' => 3,
      ]);

      $detail=DB::table('item_migration_details')->where('header_id', $id)->get();
      foreach ($detail as $key => $value) {
        StockTransaction::create([
          'warehouse_id' => $i->warehouse_to_id,
          'rack_id' => $i->rack_to_id,
          'item_id' => $value->item_id,
          'type_transaction_id' => 38,
          'code' => $i->code,
          'date_transaction' => Carbon::now(),
          'description' => 'Penerimaan Barang pada Mutasi - '.$i->code,
          'qty_masuk' => $value->qty,
        ]);
        sleep(1);
        WarehouseStock::whereRaw("warehouse_id = $i->warehouse_from_id and item_id = $value->item_id")->update([
          'transit' => DB::raw("transit-$value->qty")
        ]);
      }
      DB::commit();

      return Response::json(['message' => 'Item transaksi mutasi transfer berhasil dimasukkan ke rak penyimpanan tujuan'],200);
    }


    public function store_detail(Request $request)
    {
      DB::beginTransaction();
      $i = ItemMigrationDetail::find($request->id);
      if($i == null) {      
          return Response::json(['message' => 'ID Detail mutasi transfer tidak ditemukan'],422,[],JSON_NUMERIC_CHECK);
      } else {
          $item_migration = DB::table('item_migrations')
          ->whereId($i->header_id)
          ->select('status')
          ->first();

          if($item_migration->status > 1) {
              return Response::json(['message' => 'Detail barang dari transaksi yang sudah disetujui tidak boleh diubah'],422,[],JSON_NUMERIC_CHECK);            
          }
      }
      $i->update([
        'qty' => $request->qty,
      ]);
      DB::commit();
      return Response::json(['message' => 'Item transaksi mutasi transfer berhasil di-update'],200,[],JSON_NUMERIC_CHECK);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      $i = ItemMigrationDetail::find($id);
      if($i == null) {

          return Response::json(['message' => 'Detail barang tidak ditemukan'],422);
      }

      $i->delete();
      DB::commit();
      return Response::json(['message' => 'Item transaksi mutasi transfer berhasil dihapus'],200);
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
      $i = ItemMigration::find($id);
      if($i == null) {

          return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'],500);
      }
      else {
          if($i->warehouse_from_id == $i->warehouse_to_id) {  

            return Response::json(['message' => 'Transaksi mutasi transfer tidak ditemukan'], 422 );
          }
          else {
            if($i->status != 1) {
                return Response::json(['message' => 'Transaksi yang sudah disetujui tidak bisa dihapus'],422);
            }

          }
      }
      ItemMigrationDetail::where('header_id', $id)->delete();
      if($i->warehouse_from_id == $i->warehouse_to_id) {
        $transaksi = 'putaway';
      }
      else {
        $transaksi = 'mutasi transfer';
      }
      $i->delete();
      return Response::json(['message' => 'Transaksi mutasi transfer  berhasil dihapus'],200);
    }
}

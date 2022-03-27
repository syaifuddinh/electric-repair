<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\ItemMigration;
use App\Model\StokOpnameWarehouse;
use App\Abstracts\Inventory\StokOpnameWarehouse AS SOW;
use App\Model\StokOpnameWarehouseDetail;
use App\Model\ItemMigrationDetail;
use App\Model\StockTransaction;
use App\Model\WarehouseStock;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Rack;
use App\Model\StorageType;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class StokOpnameWarehouseController extends Controller
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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      // dd($request);
      $request->validate([
        'warehouse_id' => 'required',
        'type' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'stokOpnameWarehouse');
      $code->setCode();
      $trx_code = $code->getCode();

      // $date_transaction = preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $date_transaction);

      $s=StokOpnameWarehouse::create([
            'warehouse_id' => $request->warehouse_id,
            'type' => $request->type,
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'status' => 1
      ]);

      // Input detail stok opname

      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $reqDetail = new Request($value);
        $reqDetail->validate([
            'item_id' => 'required',
            'warehouse_receipt_detail_id' => 'required',
            'rack_id' => 'required',
            'qty' => 'required',
            'qty_riil' => 'required'
        ]);
        
        DB::table('stok_opname_warehouse_details')->insert([
          'header_id' => $s->id,
          'item_id' => $value['item_id'],
          'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'] ?? null,
          'rack_id' => $value['rack_id'],
          'stock_sistem' => $value['qty'] ?? 0,
          'stock_riil' => $value['qty_riil'] ?? 0,
        ]);
      }
      DB::commit();

      return Response::json(['message' => 'Data successfully saved'],200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=StokOpnameWarehouse::with('warehouse', 'customer', 'warehouse.company', 'warehouse_receipt', 'warehouse_receipt.customer')
      ->leftJoin('stok_opname_statuses', 'stok_opname_statuses.id', 'stok_opname_warehouses.status')
      ->where('stok_opname_warehouses.id', $id)
      ->select('stok_opname_warehouses.*', 'stok_opname_statuses.name AS status_name', 'stok_opname_statuses.slug AS status_slug')
      ->first();

      $data['detail']=StokOpnameWarehouseDetail::with('rack', 'item', 'warehouse_stock_detail', 'warehouse_receipt', 'warehouse_receipt.customer')
      ->leftJoin('items', 'items.id', 'stok_opname_warehouse_details.item_id')
      ->leftJoin('racks', 'racks.id', 'stok_opname_warehouse_details.rack_id')
      ->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'stok_opname_warehouse_details.warehouse_receipt_detail_id')
      ->leftJoin('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
      ->select('stok_opname_warehouse_details.*', 'warehouse_receipts.code AS warehouse_receipt_code', 'racks.code AS rack_code', 'items.name AS item_name')
      ->where('stok_opname_warehouse_details.header_id', $id)->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve($id)
    {

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            SOW::validateIsApproved($id);
            $s = StokOpnameWarehouse::find($id);

            $s->update([
                'status' => 2
            ]);

            $detail = StokOpnameWarehouseDetail::with('stok_opname_warehouse', 'stok_opname_warehouse.warehouse_receipt')->where('header_id', $id)->get();

            $type_transaction = DB::table('type_transactions')->where('slug', 'stokOpnameWarehouse')->first();
            foreach($detail as $unit) {
                $qty_masuk = 0;
                $qty_keluar = 0;
                if($unit->stock_riil > $unit->stock_sistem) {
                  $qty_masuk = $unit->stock_riil - $unit->stock_sistem;
                }
                else {
                  $qty_keluar = $unit->stock_sistem - $unit->stock_riil;

                }
                if($qty_masuk == 0 && $qty_keluar == 0) {
                  continue;
                }
                else {
                  StockTransaction::create([
                    'item_id' => $unit->item_id,
                    'warehouse_receipt_detail_id' => $unit->warehouse_receipt_detail_id,
                    'warehouse_id' => $s->warehouse_id,
                    'rack_id' => $unit->rack_id,
                    'type_transaction_id' => $type_transaction->id,
                    'qty_masuk' => $qty_masuk,
                    'qty_keluar' => $qty_keluar,
                    'description' => 'Penyesuaian Stok',
                    'date_transaction' => DB::raw('(SELECT DATE_FORMAT(NOW(), "%Y-%m-%d"))'),
                  ]);
                }
            }

          DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }

        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }



    public function delete_detail($id)
    {
      DB::beginTransaction();
      StokOpnameWarehouseDetail::find($id)->delete();
      DB::commit();
      return Response::json(null,200);
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

      // dd($request);
      $request->validate([
        'warehouse_id' => 'required',
        'type' => 'required',
      ]);
      DB::beginTransaction();

      $s= StokOpnameWarehouse::find($id);
      $s=$s->update([
        'warehouse_id' => $request->warehouse_id,
        'type' => $request->type,
      ]);

      // Update detail stok opname
      StokOpnameWarehouseDetail::where('header_id', $id)->delete();
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }


        DB::table('stok_opname_warehouse_details')->insert([
          'header_id' => $id,
          'item_id' => $value['item_id'],
          'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'],
          'rack_id' => $value['rack_id'],
          'stock_sistem' => $value['qty'],
          'stock_riil' => $value['qty_riil']
        ]);
      }
      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
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
      try {
            StokOpnameWarehouse::find($id)->delete();
            DB::commit();
      }
      catch(\Exception $e) {
        DB::rollback();
        return Response::json(['message' => $e],500);
      }

      return Response::json(null);

    }


}

<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\ItemMigration;
use App\Model\ItemMigrationDetail;
use App\Model\StockTransaction;
use App\Model\WarehouseStock;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class PalletMigrationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['warehouse']=DB::table('warehouses')->selectRaw('id,name')->get();
      $data['storage']=DB::table('racks')->selectRaw('id,code as name')->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
      // dd($request);
      $request->validate([
        'warehouse_from_id' => 'required',
        'warehouse_to_id' => 'required',
        'date_transaction' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'itemMigration');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=ItemMigration::create([
        'warehouse_from_id' => $request->warehouse_from_id,
        'warehouse_to_id' => $request->warehouse_to_id,
        'rack_from_id' => $request->rack_id,
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
        ItemMigrationDetail::create([
          'header_id' => $i->id,
          'item_id' => $value['item_id'],
          'qty' => $value['qty'],
        ]);
      }
      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=ItemMigration::with('warehouse_from','warehouse_to','rack_from','rack_to')->where('id', $id)->first();
      $data['detail']=DB::table('item_migration_details')
      ->leftJoin('items','items.id','item_migration_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->where('item_migration_details.header_id', $id)
      ->selectRaw('
      item_migration_details.*,
      items.name as item_name,
      items.barcode,
      items.code as item_code,
      categories.name as category_name
      ')->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function item_out($id)
    {
      DB::beginTransaction();
      $i=ItemMigration::find($id);
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

      return Response::json(null,200);
    }
    public function item_in($id)
    {
      DB::beginTransaction();
      $i=ItemMigration::find($id);
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

      return Response::json(null,200);
    }


    public function store_detail(Request $request)
    {
      DB::beginTransaction();
      ItemMigrationDetail::find($request->id)->update([
        'qty' => $request->qty,
      ]);
      DB::commit();
      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      ItemMigrationDetail::find($id)->delete();
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

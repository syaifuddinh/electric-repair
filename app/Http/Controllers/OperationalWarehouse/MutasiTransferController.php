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
use App\Abstracts\Inventory\ItemMigration AS IM;
use App\Abstracts\Inventory\ItemMigrationDetail AS IMD;
use App\Abstracts\Inventory\ItemMigrationReceipt;

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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_from_id' => 'required',
            'date_transaction' => 'required'
        ]);
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            IM::store($request->all());
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
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
        $data['detail'] = IMD::index($id); 

        return Response::json($data,200);
    }

    public function item_out($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            IM::itemOut(auth()->id(), $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
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
          'no_surat_jalan' => $value->no_surat_jalan,
          'rack_id' => $i->rack_to_id,
          'no_surat_jalan' => $value->no_surat_jalan,
          'item_id' => $value->item_id,
          'warehouse_receipt_detail_id' => $value->warehouse_receipt_detail_id,
          'type_transaction_id' => 38,
          'code' => $i->code,
          'date_transaction' => Carbon::now(),
          'description' => 'Penerimaan Barang pada Mutasi Transfer - '.$i->code,
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
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            IMD::updateQty($request->qty, $request->id);
            DB::commit();
        } catch(Exception $e) {
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

    public function update(Request $request, $id)
    {
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            IM::update($request->all(), $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 25-03-2021
      Description : Menyimpan penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function storeReceipt(Request $request, $id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            $params = $request->all();
            ItemMigrationReceipt::storeReceipt($params, $id);
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
      ItemMigrationDetail::where('header_id', $id)->delete();
      ItemMigration::find($id)->delete();
    }
}

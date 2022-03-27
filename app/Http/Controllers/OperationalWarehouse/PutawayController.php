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
use Exception;
use App\Abstracts\Inventory\Putaway;
use App\Abstracts\Inventory\PutawayDetail;

class PutawayController extends Controller
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

    /*
      Date : 30-04-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            Putaway::store($request->all());
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
      Date : 30-04-2021
      Description : Menampilkan data
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
      $data['item'] = Putaway::show($id);
      $data['detail']=DB::table('item_migration_details')
      ->leftJoin('items','items.id','item_migration_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'item_migration_details.warehouse_receipt_detail_id')
      ->leftJoin('racks AS origin_racks', 'origin_racks.id', 'item_migration_details.rack_id')
      ->leftJoin('racks AS destination_racks', 'destination_racks.id', 'item_migration_details.destination_rack_id')
      ->join('warehouse_receipts', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
      ->where('item_migration_details.header_id', $id)
      ->selectRaw('
      item_migration_details.*,
      origin_racks.code as origin_rack_code,
      destination_racks.code as destination_rack_code,
      items.name as item_name,
      items.barcode,
      items.code as item_code,
      warehouse_receipts.code AS warehouse_receipt_code,
      categories.name as category_name
      ')->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 29-08-2021
      Description : Membuat pengeluaran barang
      Developer : Didin
      Status : Edit
    */
    public function item_out($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            Putaway::itemOut(auth()->id(), $id);
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
      Date : 29-08-2021
      Description : Membuat penerimaan barang pada rak tujuan
      Developer : Didin
      Status : Edit
    */
    public function item_in($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            Putaway::itemIn(auth()->id(), $id);
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
      Date : 05-03-2021
      Description : Ubah jumlah barang
      Developer : Didin
      Status : Edit
    */
    public function store_detail(Request $request)
    {
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            PutawayDetail::updateQty($request->qty, $request->id);
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

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            Putaway::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);

    }
}

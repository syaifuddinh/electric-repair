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

class PutawayController extends Controller
{
    private $ctrl;

    public function __construct() {
        $this->ctrl = new \App\Http\Controllers\OperationalWarehouse\PutawayController();
    }

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
    return $this->ctrl->store($request);
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
            return Response::json(['message' => 'Transaksi putaway tidak ditemukan'], 422);
        }
        else {
            if($i->warehouse_from_id != $i->warehouse_to_id) {

                return Response::json(['message' => 'Transaksi putaway tidak ditemukan'], 422);
            }
        }

        return $this->ctrl->show($id);
    }

    public function item_out($id)
    {
        return $this->ctrl->item_out($id);
    }
    public function item_in($id)
    {
        return $this->ctrl->item_in($id);
    }


public function store_detail(Request $request)
{
    return $this->ctrl->store_detail($request);
}

public function delete_detail($id)
{
    if(ItemMigrationDetail::find($id) == null) {
        return Response::json(['message' => 'Item transaksi putaway tidak ditemukan'], 422);
    }
    DB::beginTransaction();
    ItemMigrationDetail::find($id)->delete();
    DB::commit();
    return Response::json(['message' => 'Item transaksi putaway berhasil di-hapus'],200,[],JSON_NUMERIC_CHECK);
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

    public function destroy($id)
    {
        return $this->ctrl->destroy($id);

    }
}

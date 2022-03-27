<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\StockTransaction;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use App\Abstracts\Inventory\ItemDeletion;
use App\Abstracts\Inventory\ItemDeletionDetail;

class PalletDeletionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
            'warehouse_id' => 'required',
            'date_transaction' => 'required',
        ]);
      
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['create_by'] = auth()->id();
            ItemDeletion::store($params);
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
      $data['item']=ItemDeletion::show($id);
      $data['detail']=ItemDeletionDetail::index($id);

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 10-03-2020
      Description : men-setujui penggunaan barang dan mengurangi stok 
                    pada inventori
      Developer : Didin
      Status : Edit
    */
    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            ItemDeletion::approve(auth()->id(), $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function store_detail(Request $request)
    {
      DB::beginTransaction();
      itemDeletionDetail::find($request->id)->update([
        'qty' => $request->qty,
      ]);
      DB::commit();
      return Response::json(null,200);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      itemDeletionDetail::find($id)->delete();
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
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            ItemDeletion::update($request->all(), $id);
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      itemDeletion::find($id)->delete();
      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }


}

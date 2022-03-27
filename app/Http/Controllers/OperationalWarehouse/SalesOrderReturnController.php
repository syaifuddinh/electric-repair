<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
use App\Abstracts\Sales\SalesOrderReturn;
use App\Abstracts\Sales\SalesOrderReturnDetail;
use App\Abstracts\Sales\SalesOrderReturnReceipt;
use Carbon\Carbon;

class SalesOrderReturnController extends Controller
{
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

    public function cari_so(Request $request)
    {
      $data['item']=DB::table('sales_orders')->where('id', $request->id)->first();
      $data['detail']=DB::table('sales_order_details')
      ->leftJoin('items','items.id','sales_order_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->where('sales_order_details.header_id', $request->id)
      ->selectRaw('
        sales_order_details.*,
        items.barcode,
        items.name as item_name,
        categories.name as category
      ')->get();

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
        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['create_by'] = auth()->id();
            SalesOrderReturn::store($params);
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
        $data['item'] = SalesOrderReturn::show($id);
        $data['detail'] = SalesOrderReturnDetail::index($id); 
        
        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve(Request $request,$id)
    {
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
        $msg = 'OK';
        DB::beginTransaction();
        try {
            $params = $request->all();
            SalesOrderReturn::update($params, $id);
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
      Date : 13-05-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            SalesOrderReturn::destroy($id);
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
      Date : 13-05-2021
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
            SalesOrderReturnReceipt::storeReceipt($params, $id);
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

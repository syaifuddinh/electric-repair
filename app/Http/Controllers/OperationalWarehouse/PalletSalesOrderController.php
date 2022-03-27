<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\SalesOrder;
use App\Model\SalesOrderDetail;
use App\Model\Warehouse;
use App\Model\StockTransaction;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class PalletSalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['warehouse']=DB::table('warehouses')->selectRaw('id,name')->get();
      $data['customer']=DB::table('contacts')->selectRaw('id,name')->get();
      $data['company']=CompanyAdmin(auth()->id());

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['customer']=DB::table('contacts')->whereRaw('is_pelanggan = 1')->selectRaw('id,concat(name,", ",address) as name')->get();
      $data['warehouse']=DB::table('warehouses')->selectRaw('id,name,company_id')->get();
      $data['item']=DB::table('items')->selectRaw('id,name,barcode,part_number,code')->get();
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
        'warehouse_id' => 'required',
        'date_transaction' => 'required',
        'customer_id' => 'required',
        'company_id' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'salesOrder');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=SalesOrder::create([
        'company_id' => $request->company_id,
        'warehouse_id' => $request->warehouse_id,
        'customer_id' => $request->customer_id,
        'code' => $trx_code,
        'date_transaction' => Carbon::parse($request->date_transaction),
        'create_by' => auth()->id(),
        'description' => $request->description
      ]);

      foreach ($request->detail as $key => $value) {
        if (!$value) {
          continue;
        }
        SalesOrderDetail::create([
          'header_id' => $i->id,
          'item_id' => $value['item_id'],
          'qty' => $value['qty'],
          'price' => $value['price'],
          'total_price' => $value['price']*$value['qty'],
        ]);
      }
      DB::commit();

      return Response::json(null,200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['item']=SalesOrder::with('warehouse','customer')->where('id', $id)->first();
        $data['detail']=DB::table('sales_order_details')
        ->leftJoin('items','items.id','sales_order_details.item_id')
        ->leftJoin('categories','categories.id','items.category_id')
        ->where('sales_order_details.header_id', $id)
        ->selectRaw('
        sales_order_details.*,
        items.name as item_name,
        items.barcode,
        items.code as item_code,
        categories.name as category_name
        ')->get();

        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve($id)
    {
      DB::beginTransaction();
      $i=SalesOrder::find($id);
      $i->update([
        'status' => 2,
        'approve_by' => auth()->id(),
        'date_approve' => Carbon::now()
      ]);

      $detail=DB::table('sales_order_details')->where('header_id', $id)->get();
      foreach ($detail as $key => $value) {
        StockTransaction::create([
          'warehouse_id' => $i->warehouse_id,
          'item_id' => $value->item_id,
          'type_transaction_id' => 36,
          'code' => $i->code,
          'date_transaction' => Carbon::now(),
          'description' => 'Pengeluaran Barang pada Penjualan - '.$i->code,
          'qty_keluar' => $value->qty,
          'harga_keluar' => $value->price,
        ]);
      }
      DB::commit();

      return Response::json(null,200);
    }

    public function store_detail(Request $request)
    {
      DB::beginTransaction();
      SalesOrderDetail::find($request->id)->update([
        'qty' => $request->qty,
        'price' => $request->price,
        'total_price' => $request->qty*$request->price
      ]);
      DB::commit();
      return Response::json(null,200);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      SalesOrderDetail::find($id)->delete();
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
      DB::beginTransaction();
      SalesOrder::find($id)->delete();
      DB::commit();

      return Response::json(null,200);
    }
}

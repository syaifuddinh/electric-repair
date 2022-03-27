<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\PurchaseOrderReturn;
use App\Model\PurchaseOrderReturnDetail;
use App\Model\StockTransaction;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class PurchaseOrderReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['warehouse']=DB::table('warehouses')->selectRaw('id,name')->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['warehouse']=DB::table('warehouses')->selectRaw('id,name,company_id')->get();
        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function cari_po(Request $request)
    {
      $data['item']=DB::table('purchase_orders')->where('id', $request->id)->first();
      $data['detail']=DB::table('purchase_order_details')
      ->leftJoin('items','items.id','purchase_order_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->leftJoin('receipts','receipts.po_id','purchase_order_details.header_id')
      ->leftJoin('receipt_details', function($join){
        $join->on('receipt_details.item_id','items.id');
        $join->on('receipt_details.header_id','receipts.id');
      })
      ->where('purchase_order_details.header_id', $request->id)
      ->selectRaw('
        purchase_order_details.*,
        items.barcode,
        items.name as item_name,
        categories.name as category,
        sum(receipt_details.qty) as qtys
      ')->groupBy('purchase_order_details.id')->get();

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
        'purchase_order_id' => 'required',
        'warehouse_id' => 'required',
        'date_transaction' => 'required',
        'company_id' => 'required',
      ],[
        'purchase_order_id.required' => 'No. PO harus diisi',
        'warehouse_id.required' => 'Gudang harus diisi',
        'date_transaction.required' => 'Tanggal harus diisi',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'PurchaseOrderReturn');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=PurchaseOrderReturn::create([
        'purchase_order_id' => $request->purchase_order_id,
        'warehouse_id' => $request->warehouse_id,
        'create_by' => auth()->id(),
        'code' => $trx_code,
        'date_transaction' => Carbon::parse($request->date_transaction),
        'description' => $request->description,
      ]);
      foreach ($request->detail as $key => $value) {
        if ($value['qty_return']<1) {
          continue;
        }
        PurchaseOrderReturnDetail::create([
          'header_id' => $i->id,
          'item_id' => $value['item_id'],
          'po_qty' => $value['qty'],
          'qty' => $value['qty_return'],
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
      $data['item']=PurchaseOrderReturn::with('po','po.supplier','warehouse')->where('id', $id)->first();
      $data['detail']=DB::table('purchase_order_return_details')
      ->leftJoin('items','items.id','purchase_order_return_details.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->where('purchase_order_return_details.header_id', $id)
      ->selectRaw('
        purchase_order_return_details.*,
        items.name,
        items.barcode,
        items.code,
        categories.name as category_name
      ')->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve($id)
    {
      DB::beginTransaction();
      $i=PurchaseOrderReturn::find($id);
      $i->update([
        'status' => 2,
        'approve_by' => auth()->id(),
        'date_approve' => Carbon::now()
      ]);

      $detail=DB::table('purchase_order_return_details')->where('header_id', $id)->get();
      foreach ($detail as $key => $value) {
        StockTransaction::create([
          'warehouse_id' => $i->warehouse_id,
          'item_id' => $value->item_id,
          'type_transaction_id' => 35,
          'code' => $i->code,
          'date_transaction' => Carbon::now(),
          'description' => 'Pengeluaran Barang pada Retur Pembelian - '.$i->code,
          'qty_keluar' => $value->qty,
        ]);
      }
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
      $i=PurchaseOrderReturn::find($id)->delete();
      DB::commit();

      return Response::json(null,200);
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Receipt;
use App\Model\ReceiptDetail;
use App\Model\ReceiptList;
use App\Model\ReceiptListDetail;
use App\Model\Retur;
use App\Model\ReturDetail;
use App\Model\ReturReceipt;
use App\Model\ReturReceiptDetail;
use App\Model\Company;
use App\Model\Warehouse;
use App\Model\Rack;
use App\Model\TypeTransaction;
use App\Model\StockTransaction;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\Retur AS R;
use App\Abstracts\Inventory\ReturDetail AS RD;
use Response;
use DB;

class ReturController extends Controller
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
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['create_by'] = auth()->id();
            R::store($params);
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
      $data['item'] = R::show($id);
      $data['detail'] = RD::index($id);

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function receive($id)
    {
      $data['item']=Retur::with('company','receipt_list.receipt','receipt_list','supplier')->where('id', $id)->first();
      $data['warehouse']=Warehouse::where('company_id', $data['item']->company_id)->get();
      $data['detail']=ReturDetail::with('item','item.category')->where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
            R::update($request->all(), $id);
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
        //
    }

    public function cari_list($cid)
    {
      $data['item']=ReceiptList::leftJoin('receipts','receipt_lists.header_id','=','receipts.id')->whereRaw("receipt_lists.id not in (select receipt_list_id from returs) and receipts.company_id = $cid")->select('receipt_lists.*','receipts.code as receipt_code')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_penerimaan($id)
    {
      $data['item']=ReceiptList::with('warehouse','receipt','receipt.purchase_order','receipt.purchase_order.supplier')->where('id',$id)->get();
      $data['detail']=ReceiptListDetail::with('item','item.category')->where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_receive(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'warehouse_id' => 'required',
        'date_receipt' => 'required',
        'delivery_no' => 'required',
        'receiver' => 'required',
      ]);
      DB::beginTransaction();
      $r=Retur::find($id);

      $tp=TypeTransaction::where('slug','receipt')->first();
      $code = new TransactionCode($r->company_id, 'receipt');
      $code->setCode();
      $trx_code = $code->getCode();

      $rr=ReturReceipt::create([
        'header_id' => $id,
        'warehouse_id' => $request->warehouse_id,
        'company_id' => $r->company_id,
        'date_receipt' => dateDB($request->date_receipt),
        'receiver' => $request->receiver,
        'deliver_no' => $request->delivery_no,
        'description' => $request->description,
        'code' => $trx_code,
        'create_by' => auth()->id(),
      ]);
      foreach ($request->detail as $key => $value) {
        ReturReceiptDetail::create([
          'header_id' => $rr->id,
          'item_id' => $value['item_id'],
          'total_retur' => $value['qty_retur'],
          'total_receipt' => $value['qty_terima'],
          'retur_detail_id' => $value['detail_id'],
        ]);

        ReturDetail::find($value['detail_id'])->update([
          'receive' => DB::raw('receive+'.$value['qty_terima'])
        ]);

        $rack = Rack::where('warehouse_id', $request->warehouse_id)->first();

        StockTransaction::create([
          'warehouse_id' => $request->warehouse_id,
          'rack_id' => $rack->id,
          'item_id' => $value['item_id'],
          'type_transaction_id' => $tp->id,
          'code' => $trx_code,
          'date_transaction' => dateDB($request->date_receipt),
          'description' => "Penerimaan Barang Retur - ".$trx_code,
          'qty_masuk' => $value['qty_terima'],
          // 'harga_masuk' => $item->initial_cost,
        ]);

      }
      $sumRetur=ReturDetail::where('header_id', $id)->sum('qty_retur');
      $sumTerima=ReturDetail::where('header_id', $id)->sum('receive');
      if ($sumTerima>=$sumRetur) {
        $r->update([
          'status' => 3
        ]);
      } else {
        $r->update([
          'status' => 2
        ]);
      }
      DB::commit();
      return Response::json(null);
    }


    /*
      Date : 10-03-2020
      Description : men-setujui data
      Developer : Didin
      Status : Edit
    */
    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            R::approve($id);
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

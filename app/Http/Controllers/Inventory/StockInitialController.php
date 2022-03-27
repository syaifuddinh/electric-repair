<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Warehouse;
use App\Model\Company;
use App\Abstracts\Inventory\StockInitial;
use App\Model\StockTransaction;
use App\Model\TypeTransaction;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\AccountDefault;
use App\Utils\TransactionCode;
use Response;
use DB;

class StockInitialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['warehouse']=DB::table('warehouses')->select('id','company_id','name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['items']=Item::with('category')->whereRaw('customer_id IS NULL')->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
            StockInitial::store($params);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['warehouse']=Warehouse::with('company')->get();
      $data['items']=Item::with('category')->get();
      $data['item']=StockInitial::find($id);

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
      $request->validate([
        'date_transaction' => 'required',
        'warehouse_id' => 'required',
        'item_id' => 'required',
        'qty' => 'required',
        'price' => 'required|min:1',
      ]);

      DB::beginTransaction();
      $i=StockInitial::find($id);
      $cek=StockTransaction::where('item_id', $request->item_id)->where('warehouse_id', $request->warehouse_id)->where('id','!=', $i->stock_transaction_id)->sum('qty_masuk');
      // dd($cek);
      if ($cek>0) {
        return Response::json(['message' => 'Stock sudah tersedia'],500);
      }

      $wr=Warehouse::find($request->warehouse_id);
      $it=Item::find($request->item_id);
      StockInitial::find($id)->update([
        'company_id' => $wr->company_id,
        'warehouse_id' => $request->warehouse_id,
        'item_id' => $request->item_id,
        'qty' => $request->qty,
        'price' => $request->price,
        'total' => $request->price*$request->qty,
        'description' => $request->description,
        'date_transaction' => dateDB($request->date_transaction),
      ]);
      $s=StockTransaction::find($i->stock_transaction_id)->update([
        'warehouse_id' => $request->warehouse_id,
        'item_id' => $request->item_id,
        'date_transaction' => dateDB($request->date_transaction),
        'qty_masuk' => $request->qty,
        'harga_masuk' => $request->price,
      ]);
      $si=StockInitial::find($id);
      $jd=JournalDetail::where('header_id', $i->journal_id)->get();
      foreach ($jd as $key => $value) {
        if ($value->debet>0) {
          JournalDetail::find($value->id)->update([
            'debet' => ($request->price * $request->qty)
          ]);
        } else {
          JournalDetail::find($value->id)->update([
            'credit' => ($request->price * $request->qty)
          ]);
        }
      }
      DB::commit();

      return Response::json(null);
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
      $i=StockInitial::find($id);
      $i->delete();
      StockTransaction::find($i->stock_transaction_id)->delete();
      Journal::where('id', $i->journal_id)->delete();
      DB::commit();
      return Response::json(null);
    }
}

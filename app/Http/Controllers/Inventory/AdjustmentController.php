<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WarehouseStock;
use App\Model\Company;
use App\Model\StockAdjustment;
use App\Model\StockAdjustmentDetail;
use App\Model\StockTransaction;
use App\Model\TypeTransaction;
use App\Utils\TransactionCode;
use Response;
use DB;

class AdjustmentController extends Controller
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
      $data['company']=companyAdmin(auth()->id());

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
      // dd($request);
      $request->validate([
        'company_id' => 'required',
        'warehouse_id' => 'required',
        'date_transaction' => 'required',
      ]);

      DB::beginTransaction();
      $tp=TypeTransaction::where('slug','adjustment')->first();
      $code = new TransactionCode($request->company_id, 'adjustment');
      $code->setCode();
      $trx_code = $code->getCode();

      $a=StockAdjustment::create([
        'company_id' => $request->company_id,
        'warehouse_id' => $request->warehouse_id,
        'description' => $request->description,
        'create_by' => auth()->id(),
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
      ]);

      $rack = DB::table('racks')->whereWarehouseId($request->warehouse_id)->first();
      foreach ($request->detail as $key => $value) {
        if (isset($value)) {
          StockAdjustmentDetail::create([
            'header_id' => $a->id,
            'item_id' => $value['item_id'],
            'qty' => $value['qty'],
            'description' => @$value['description'],
          ]);

          StockTransaction::create([
            'warehouse_id' => $a->warehouse_id,
            'rack_id' => $rack->id,
            'item_id' => $value['item_id'],
            'type_transaction_id' => $tp->id,
            'code' => $trx_code,
            'date_transaction' => dateDB($request->date_transaction),
            'description' => "Penyesuaian Stock - ".$trx_code,
            'qty_keluar' => $value['stock']-$value['qty'],
          ]);
        }
      }
      DB::commit();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=StockAdjustment::with('company','warehouse')->where('id', $id)->first();
      $data['detail']=StockAdjustmentDetail::with('item','item.category')->where('header_id', $id)->get();

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

    public function cari_item(Request $request)
    {
      $ws=DB::table('warehouse_stocks')
      ->leftJoin('items','items.id','warehouse_stocks.item_id')
      ->leftJoin('categories','categories.id','items.category_id')
      ->where('qty','>',0)
      ->selectRaw('
        warehouse_stocks.qty,
        warehouse_stocks.item_id,
        items.name,
        categories.name as category
      ')->get();
      return Response::json($ws, 200, [], JSON_NUMERIC_CHECK);
    }
}

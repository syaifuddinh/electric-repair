<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\ItemMigration;
use App\Model\Picking;
use App\Model\PickingDetail;
use App\Model\ItemMigrationDetail;
use App\Model\StockTransaction;
use App\Model\WarehouseStock;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Rack;
use App\Model\StorageType;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\PickingDetail AS PD;
use App\Abstracts\Inventory\Picking AS P;
use DB;
use Response;
use Carbon\Carbon;
use Exception;

class PickingController extends Controller
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
        $this->validate($request, [
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'date_transaction' => 'required'
        ]);

        DB::beginTransaction();
        try {
            if($request->detail_item) {
                $detail = $request->detail_item;
            } else {
                $detail = $request->detail;
            }
            $params = $request->all();
            $params['detail'] = $detail;
            $params['create_by'] = auth()->id();
            P::store($params);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            return response()->json(['message' => $message], 421);
        }


        return Response::json(['message' => 'Data successfully saved'],200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=P::show($id);

      $data['detail']=PickingDetail::with('rack:id,code', 'item:id,name,code,category_id', 'item.category')
      ->join('items', 'items.id', 'picking_details.item_id')
      ->join('racks', 'racks.id', 'picking_details.rack_id')
      ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'picking_details.warehouse_receipt_detail_id')
      ->join('warehouse_receipts', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
      ->where('picking_details.header_id', $id)
      ->selectRaw('
        picking_details.id,
        picking_details.item_id,
        picking_details.qty,
        picking_details.rack_id,
        picking_details.warehouse_receipt_detail_id,
        items.name AS name,
        items.code AS code,
        racks.code AS rack_code,
        warehouse_receipts.code AS warehouse_receipt_code,
        (SELECT SUM(qty) FROM warehouse_stock_details WHERE rack_id = picking_details.rack_id AND warehouse_receipt_detail_id = picking_details.warehouse_receipt_detail_id) AS stock')
      ->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve($id)
    {
        DB::beginTransaction();
        $status_code = 200;
        $data['message'] = 'Data successfully approved';
        try {
            $exist = DB::table('pickings')
            ->whereId($id)
            ->first();
            if(!$exist) {
                throw new Exception('Data not found');
            }
            if($exist->status == 2) {
                throw new Exception('Data was approved');
            }
            DB::table('pickings')
            ->whereId($id)
            ->update([
                'status' => 2
            ]);
            DB::commit();
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }


    public function store_detail(Request $request)
    {
      DB::beginTransaction();
      PickingDetail::find($request->id)->update([
        'qty' => $request->qty,
      ]);
      DB::commit();
      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      PickingDetail::find($id)->delete();
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
        $request->validate([
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'date_transaction' => 'required',
        ]);


        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            if($request->detail_item) {
                $detail = $request->detail_item;
            } else {
                $detail = $request->detail;
            }
            $params = $request->all();
            $params['detail'] = $detail;
            P::update($params, $id);
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
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            P::destroy($id);
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

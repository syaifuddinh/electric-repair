<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\PurchaseOrder AS PO;
use App\Abstracts\PurchaseOrderDetail AS POD;
use App\Abstracts\Inventory\PurchaseOrderStatus;
use App\Model\PurchaseRequest;
use App\Model\PurchaseRequestDetail;
use App\Model\PurchaseOrder;
use App\Model\PurchaseOrderDetail;
use App\Model\Company;
use App\Model\Warehouse;
use App\Model\Item;
use App\Model\Contact;
use DB;
use Response;
use Exception;

class PurchaseOrderController extends Controller
{

    public function index()
    {
    }

    public function create()
    {
    }

    /*
      Date : 28-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'supplier_id' => 'required'
        ]);
        $data['message'] = 'Data successfully saved';
        $status_code = 200;
        try {
            $params = [];
            $params['company_id'] = $request->company_id;
            $params['warehouse_id'] = $request->warehouse_id;
            $params['supplier_id'] = $request->supplier_id;
            $params['payment_type'] = $request->payment_type;
            $params['description'] = $request->description;
            $params['po_date'] = $request->po_date;
            $params['detail'] = $request->detail;
            $purchase_order_id = PO::store($params);
            
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

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
        $data['item'] = PO::show($id); 

        $data['detail']=POD::index($id);

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

    /*
      Date : 28-06-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id)
    {

        $request->validate([
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'supplier_id' => 'required'
        ]);
        $data['message'] = 'Data successfully saved';
        $status_code = 200;
        DB::beginTransaction();
        try {
            $po = DB::table('purchase_orders')
            ->whereId($id)
            ->first();
            if(!$po)
                throw new Exception('Data not found');

            if($po->po_status > 1)
                throw new Exception('Data can"t be update because this data has approved');
            $params = [];
            $params['company_id'] = $request->company_id;
            $params['warehouse_id'] = $request->warehouse_id;
            $params['supplier_id'] = $request->supplier_id;
            $params['payment_type'] = $request->payment_type;
            $params['description'] = $request->description;
            $params['po_date'] = $request->po_date;
            PO::update($params, $id);
            $purchase_order_id = $id;
            DB::table('purchase_order_details')
            ->whereHeaderId($id)
            ->delete();
            if(is_array($request->detail)) {
                foreach($request->detail as $params) {
                    $args = [];
                    $args['price'] = $params['price'] ?? null;
                    $args['item_id'] = $params['item_id'] ?? null;
                    $args['purchase_request_detail_id'] = $params['purchase_request_detail_id'] ?? null;
                    $args['qty'] = $params['qty'] ?? 0;
                    $args['price'] = $params['price'] ?? 0;
                    POD::store($args, $purchase_order_id);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);

        return response()->json(['message' => 'Data berhasil diupdate']);
    }

    /*
      Date : 28-06-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public function approve($id)
    {
        $data['message'] = 'Data successfully updated';
        $status_code = 200;
        DB::beginTransaction();
        try {
            PO::approve($id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }

    /*
      Date : 28-06-2021
      Description : Mendapatkan daftar status purchase order
      Developer : Didin
      Status : Create
    */
    public function indexStatus()
    {
        $status_code = 200;
        $data['message'] = 'OK';
        $data['data'] = PurchaseOrderStatus::index();

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
        $data['message'] = 'Data successfully deleted';
        $status_code = 200;
        DB::beginTransaction();
        try {
            PO::destroy($id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }
}

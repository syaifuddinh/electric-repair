<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\PurchaseRequest;
use App\Model\PurchaseRequestDetail;
use App\Model\PurchaseOrder;
use App\Model\PurchaseOrderDetail;
use App\Model\Company;
use App\Model\Warehouse;
use App\Model\Vehicle;
use App\Model\Item;
use App\Model\Contact;
use App\Utils\TransactionCode;
use DB;
use Carbon\Carbon;
use Response;
use App\Abstracts\Journal AS J;
use App\Abstracts\Inventory\Warehouse AS WH;
use App\Abstracts\Inventory\PurchaseRequest AS PR;
use Exception;

class PurchaseRequestController extends Controller
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
      $user_id = auth()->id();
      $user = DB::table('users')->where('id', $user_id)->first();
      $user_name = $user->name ;


      $data['user_name']=$user_name;

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
        'warehouse_id' => 'required',
        'supplier_id' => 'required',
        'date_request' => 'required',
        'date_needed' => 'required',
      ]);

      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'purchaseRequest');
      $code->setCode();
      $trx_code = $code->getCode();
      $wh = WH::show($request->warehouse_id);
      $request->company_id = $wh->company_id;
      $i=PurchaseRequest::create([
        'company_id' => $request->company_id,
        'warehouse_id' => $request->warehouse_id,
        'supplier_id' => $request->supplier_id,
        'description' => $request->description,
        'is_pallet' => $request->is_pallet??0,
        'create_by' => auth()->id(),
        'status' => 1,
        'code' => $trx_code,
        'date_needed' => dateDB($request->date_needed),
        'date_request' => dateDB($request->date_request),
      ]);

      foreach ($request->detail as $key => $value) {
        if (isset($value)) {
          $reqDetail = new Request($value);
            $reqDetail->validate([
                'item_id' => 'required',
                'qty' => 'required',
            ]);

          PurchaseRequestDetail::create([
            'header_id' => $i->id,
            'item_id' => $value['item_id'],
            'qty' => $value['qty'],
          ]);
        }
      }
      DB::commit();

      return Response::json(null);
    }

    public function reject(Request $request, $id)
    {
      DB::beginTransaction();
      PurchaseRequest::find($id)->update([
        'status' => 0,
        'reject_reason' => $request->reject_reason,
        'date_reject' => Carbon::now(),
        'reject_by' => auth()->id()
      ]);
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
        $data['item'] = PR::show($id);
        $data['detail'] = PurchaseRequestDetail::with('vehicle','item','item.category','po_detail')
        ->leftJoin('items', 'items.id', 'purchase_request_details.item_id')
        ->where('purchase_request_details.header_id', $id)
        ->select('purchase_request_details.*', 'items.id AS item_id', 'items.name AS item_name')
        ->get();

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
      $data['company']=companyAdmin(auth()->id());
      $data['item']=Item::with('category')->get();
      $data['supplier']=Contact::whereRaw("is_supplier = 1 or is_vendor = 1 and vendor_status_approve = 2")->get();
      $data['i']=PurchaseRequest::with('company','warehouse','supplier')->where('id', $id)->first();
      $data['detail']=PurchaseRequestDetail::with('vehicle','item','item.category')->where('header_id', $id)->get();
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
        'company_id' => 'required',
        'warehouse_id' => 'required',
        'supplier_id' => 'required',
        'date_request' => 'required',
        'date_needed' => 'required',
      ]);
      
      $msg = 'Data successfully saved';

      DB::beginTransaction();

      $i=PurchaseRequest::find($id)->update([
        'company_id' => $request->company_id,
        'warehouse_id' => $request->warehouse_id,
        'supplier_id' => $request->supplier_id,
        'description' => $request->description,
        'date_needed' => dateDB($request->date_needed),
        'date_request' => dateDB($request->date_request),
      ]);
      PurchaseRequestDetail::where('header_id', $id)->delete();

      foreach ($request->detail as $key => $value) {
        if (isset($value)) {
          PurchaseRequestDetail::create([
            'header_id' => $id,
            'item_id' => $value['item_id'],
            'qty' => $value['qty'],
          ]);
        }
      }
      DB::commit();
      $data['message'] = $msg;
      return Response::json($data);
    }

    public function store_detail(Request $request, $id)
    {
      DB::beginTransaction();
      PurchaseRequestDetail::find($id)->update([
        'item_id' => $request->item_id,
        'qty' => $request->qty,
      ]);
      DB::commit();
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      PurchaseRequestDetail::find($id)->delete();
      DB::commit();
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
      PurchaseRequest::find($id)->delete();
      DB::commit();

      return Response::json(null,200);
    }

    public function cari_gudang(Request $request)
    {
      $c=Warehouse::where('company_id', $request->company_id)->get();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }
    public function cari_kendaraan(Request $request)
    {
      $c=Vehicle::where('company_id', $request->company_id)->get();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }
    public function approve(Request $request, $id)
    {
      // dd($request);
      DB::beginTransaction();
      PurchaseRequest::find($id)->update([
        'status' => 2,
        'approve_by' => auth()->id(),
        'date_approved' => date('Y-m-d'),
      ]);
      foreach ($request->detail as $key => $value) {
        PurchaseRequestDetail::find($value['id'])->update([
          'qty_approve' => $value['qty']
        ]);
      }
      DB::commit();

      return Response::json(null);
    }

    public function create_po(Request $request, $id)
    {
        $status_code = 200;
        $data['message'] = "Data successfully updated";
        DB::beginTransaction();
        try {

            PR::createPurchaseOrder(
                $request->payment_type, 
                $request->purchase_date,
                $request->detail,
                $id
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }
        return Response::json($data, $status_code);
    }
}

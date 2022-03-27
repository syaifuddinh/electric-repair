<?php

namespace App\Http\Controllers\Api\v4\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\Contact;
use App\Model\Company;
use App\Model\InvoiceVendor;
use App\Model\InvoiceVendorDetail;
use App\Model\Payable;
use App\Model\PayableDetail;
use DB;
use Response;

class InvoiceVendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['customer']=DB::table('contacts')->where('is_vendor',1)->selectRaw('id,name')->get();
        $data['company']=companyAdmin(auth()->id());
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code','asc')->get();
      $data['contact']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2 or is_supplier = 1")->select('id','name','company_id','address')->get();
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
        'date_invoice' => 'required',
        'date_receive' => 'required',
        'vendor_id' => 'required',
        'code' => 'required',
        'detail' => 'required'
      ]);
      DB::beginTransaction();
      $i=InvoiceVendor::create([
        'company_id' => $request->company_id,
        'vendor_id' => $request->vendor_id,
        'code' => $request->code,
        'description' => $request->description,
        'date_invoice' => dateDB($request->date_invoice),
        'date_receive' => dateDB($request->date_receive),
        'create_by' => auth()->id(),
      ]);
      $sum=0;
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        InvoiceVendorDetail::create([
          'header_id' => $i->id,
          'payable_id' => $value['payable_id']??null,
          'nota_account_id' => $value['nota_account_id']??null,
          'create_by' => auth()->id(),
          'verification' => $value['verification']??0,
          'total' => $value['total']??0,
          'margin' => $value['margin']??0,
          'reff_no' => $value['reff_no']??'-',
          'is_consistent' => $value['is_consistent'],
          'description' => $value['description']??'-',
          'type' => $value['type'],
        ]);

        if ($value['payable_id']??null && $value['margin']!=0) {
          PayableDetail::create([
            'header_id' => $value['payable_id'],
            'code' => $request->code,
            'type_transaction_id' => 32,
            'date_transaction' => dateDB($request->date_invoice),
            'relation_id' => $i->id,
            'debet' => ($value['margin']>0?abs($value['margin']):0),
            'credit' => ($value['margin']<0?abs($value['margin']):0),
            'description' => $value['description']??'-',
          ]);

          Payable::find($value['payable_id'])->update([
            'is_invoice' => 1
          ]);
        }
        $sum+=$value['verification']??0;
      }
      InvoiceVendor::find($i->id)->update([
        'total' => $sum
      ]);
      DB::commit();

      return Response::json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=InvoiceVendor::with('vendor','company')->where('id', $id)->first();
      $data['detail']=InvoiceVendorDetail::where('header_id', $id)->get();
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
}

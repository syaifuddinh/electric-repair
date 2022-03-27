<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\Company;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Utils\TransactionCode;
use App\Model\CashCategory;
use Response;
use DB;

class SaldoAccountController extends Controller
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
        $data['account']=Account::where('is_base',0)->orderBy('code')->get();
        $data['company']=companyAdmin(auth()->id());
        $data['default']=AccountDefault::first();
        $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();
        
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
        DB::beginTransaction();
        
        $code = new TransactionCode($request->company_id, "saldoAwal");
        $code->setCode();
        $trx_code = $code->getCode();
        
        $journal=Journal::create([
            'company_id' => str_replace("number:","",$request->company_id),
            'code' => $trx_code,
            'date_transaction' => dateDB($request->date_transaction),
            'type_transaction_id' => 2,
            'created_by' => auth()->id(),
            'debet' => 0,
            'credit' => 0,
            'description' => $request->description,
            'status' => 1,
            'source' => 1
         ]);
            
        foreach ($request->detail as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $cekCC=cekCashCount($journal->company_id,$value['account_id']);
            if ($cekCC) {
                return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
            }
            
            JournalDetail::create([
                'header_id' => $journal->id,
                'account_id' => $value['account_id'],
                'cash_category_id' => $value['cash_category_id'],
                'debet' => $value['debet'],
                'credit' => $value['credit'],
            ]);
        }
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
        $data['item']=Journal::with('company')->where('id', $id)->first();
        $data['detail']=JournalDetail::with('account')->where('header_id', $id)->get();
        
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
        DB::beginTransaction();
        Journal::find($id)->delete();
        DB::commit();
        
        return Response::json(null);
    }
}
        
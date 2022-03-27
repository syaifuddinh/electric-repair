<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\NotaDebet;
use App\Model\Company;
use App\Model\Account;
use App\Model\Contact;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\CashTransaction;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\TypeTransaction;
use App\Model\AccountDefault;
use App\Utils\TransactionCode;
use Response;
use DB;

class NotaDebetController extends Controller
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
      $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
      $data['contact']=Contact::whereRaw('is_supplier = 1 or is_vendor = 1 and vendor_status_approve = 2')->get();
      $data['cash_transaction']=CashTransaction::with('CashTransactionDetail')->where('is_cut', 0)->where('status_cost', 3)->get();

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
        'date_transaction' => 'required',
        'amount' => 'required|integer|min:1',
        'contact_id' => 'required',
        'payable_id' => 'required',
        'account_id' => 'required',
        'contra' => 'required',
        'cash_transaction' =>'required_if:contra,1'
    ],
    [
        'amount.min' => 'nominal tidak boleh 0'
    ]);

    // if debit, cek amount tidak boleh melebihi payable
      $payable = Payable::find($request->payable_id);
      if (($request->jenis == 1) && ($payable->credit < $request->amount))
      {
        return Response::json(['errors' => ['nota potong yang diajukan melebihi nilai hutang']], 422);
    }

    DB::beginTransaction();
    $code = new TransactionCode($request->company_id, "notaDebet");
    $code->setCode();
    $trx_code = $code->getCode();
    $tptrx=TypeTransaction::where('slug','notaDebet')->first();

    $i=NotaDebet::create([
        'company_id' => $request->company_id,
        'payable_id' => $request->payable_id,
        'contact_id' => $request->contact_id,
        'date_transaction' => dateDB($request->date_transaction),
        'jenis' => $request->jenis,
        'code' => $trx_code,
        'amount' => $request->amount,
        'reff_no' => (isset($request->cash_transaction)?$request->cash_transaction['code']:null),
        'description' => $request->description,
    ]);

    PayableDetail::create([
        'header_id' => $request->payable_id,
        'type_transaction_id' => $tptrx->id,
        'relation_id' => $i->id,
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'debet' => ($request->jenis==1?$request->amount:0),
        'credit' => ($request->jenis==2?$request->amount:0),
        'description' => $request->description,
    ]);

    if (isset($request->cash_transaction)) {
        CashTransaction::find($request->cash_transaction['id'])->update(['is_cut' => 1]);
    }

    $account_default=AccountDefault::first();
    $contact=Contact::find($request->contact_id);
    if (empty($contact->akun_hutang)) {
        if (empty($account_default->hutang)) {
          return Response::json(['message' => 'Akun Hutang belum ditentukan'],500);
      } else {
          $hutang=$account_default->hutang;
      }
    } else {
        $hutang=$contact->akun_hutang;
    }
    $j=Journal::create([
        'company_id' => $request->company_id,
        'type_transaction_id' => $tptrx->id,
        'date_transaction' => dateDB($request->date_transaction),
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'description' => $request->description,
        'status' => 2
    ]);

    $cekCC=cekCashCount($request->company_id,($request->jenis==2?$hutang:$request->account_id));
    if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
    }

    JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => ($request->jenis==1?$hutang:$request->account_id),
        'debet' => $request->amount,
        'credit' => 0,
    ]);
    $cekCC=cekCashCount($request->company_id,($request->jenis==1?$hutang:$request->account_id));
    if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
    }

    JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => ($request->jenis==2?$hutang:$request->account_id),
        'debet' => 0,
        'credit' => $request->amount,
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
      $data['item']=NotaDebet::with('company','contact','payable')->where('id', $id)->first();
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
      $notaDebet = NotaDebet::find($id);
      $payable = $notaDebet->payable;
      $journal = Journal::where('code', $notaDebet->code)->first();

      DB::beginTransaction();
      $payable->debet = $payable->debet - $notaDebet->amount;
      $payable->save();
    // jika menggunakan transaksi kas, update is_cut
      if (!empty($notaDebet->reff_no)) {
        CashTransaction::where('code', $notaDebet->reff_no)->update(['is_cut' => 0]);
    }

    $journal->details()->delete();
    $journal->delete();
    $payable->payableDetails()->where([
        'relation_id' => $notaDebet->id,
        'code' => $notaDebet->code,
    ])->delete();
    $notaDebet->delete();
    DB::commit();
    }

    public function cari_hutang($id)
    {
      $c=Payable::where('contact_id', $id)->whereRaw('(credit-debet) > 0')->get();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

}

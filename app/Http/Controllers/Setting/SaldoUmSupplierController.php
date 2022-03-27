<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\TypeTransaction;
use App\Model\UmSupplier;
use App\Model\UmSupplierDetail;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Contact;
use App\Model\Company;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Utils\TransactionCode;
use Response;
use DB;
use Carbon\Carbon;

class SaldoUmSupplierController extends Controller
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
      $data['company']=companyAdmin(auth()->id());
      $data['contact']=Contact::whereRaw("is_supplier=1 OR is_vendor=1")->where('vendor_status_approve', 2)->get();

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
        'contact_id' => 'required',
        'company_id' => 'required',
        'date_transaction' => 'required',
        'nominal' => 'required|integer|min:1'
      ]);

      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, "saldoAwal");
      $code->setCode();
      $trx_code = $code->getCode();

      $tptrx=TypeTransaction::where('slug','saldoAwal')->first();
      $pay=UmSupplier::create([
        'company_id' => $request->company_id,
        'contact_id' => $request->contact_id,
        'type_transaction_id' => $tptrx->id,
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'description' => $request->description,
        'debet' => $request->nominal,
        'credit' => 0,
      ]);
      $payd=UmSupplierDetail::create([
        'header_id' => $pay->id,
        'type_transaction_id' => $tptrx->id,
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'debet' => $request->nominal,
        'credit' => 0,
        'description' => $request->description
      ]);
      $account_default=AccountDefault::first();
      if (empty($account_default->saldo_awal)) {
        return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
      }
      $contact=Contact::find($request->contact_id);
      if (empty($contact->akun_um_supplier)) {
        return Response::json(['message' => '<b>Akun Uang Muka Supplier</b> pada <b>'.$contact->name.'</b> belum ditentukan!'],500);
      } else {
        $akun_um=$contact->akun_um_supplier;
      }
      $j=Journal::create([
        'company_id' => $request->company_id,
        'type_transaction_id' => $tptrx->id,
        'created_by' => auth()->id(),
        'date_transaction' => dateDB($request->date_transaction),
        'code' => $trx_code,
        'description' => $request->description,
        'debet' => $request->nominal,
        'credit' => $request->nominal,
        'status' => 1
      ]);

      $cekCC=cekCashCount($request->company_id,$account_default->saldo_awal);
      if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
      }

      $j_saldo=JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $account_default->saldo_awal,
        'debet' => 0,
        'credit' => $request->nominal,
        'description' => $request->description,
      ]);

      $cekCC=cekCashCount($request->company_id,$akun_um);
      if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
      }

      $j_um=JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $akun_um,
        'debet' => $request->nominal,
        'credit' => 0,
        'description' => $request->description,
      ]);

      UmSupplier::find($pay->id)->update([
        'journal_id' => $j->id,
      ]);
      UmSupplierDetail::find($payd->id)->update([
        'journal_id' => $j->id,
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
      $dt=UmSupplier::with('contact','company')->where('id', $id)->first();
      return Response::json($dt, 200, [], JSON_NUMERIC_CHECK);
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
      $data['contact']=Contact::whereRaw("is_supplier=1 OR is_vendor=1")->where('vendor_status_approve', 2)->get();
      $data['item']=UmSupplier::with('contact','company')->where('id', $id)->first();
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
      DB::beginTransaction();
      $request->validate([
        'contact_id' => 'required',
        'company_id' => 'required',
        'nominal' => 'required|integer|min:1'
      ]);
      UmSupplier::find($id)->update([
        // 'company_id' => $request->company_id,
        // 'contact_id' => $request->contact_id,
        // 'type_transaction_id' => $tptrx->id,
        // 'created_by' => auth()->id(),
        // 'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        // 'date_tempo' => Carbon::parse($request->date_transaction)->addDays($request->jatuh_tempo),
        'description' => $request->description,
        // 'debet' => 0,
        'debet' => $request->nominal,
      ]);
      $payable=UmSupplier::find($id);
      $payd=UmSupplierDetail::where('header_id', $id)->where('type_transaction_id', $payable->type_transaction_id)->update([
        // 'header_id' => $pay->id,
        // 'type_transaction_id' => $tptrx->id,
        // 'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        // 'debet' => 0,
        'debet' => $request->nominal,
      ]);
      $j=Journal::find($payable->journal_id)->update([
        // 'company_id' => $request->company_id,
        // 'type_transaction_id' => $tptrx->id,
        // 'created_by' => auth()->id(),
        'date_transaction' => dateDB($request->date_transaction),
        // 'code' => $trx_code,
        'description' => $request->description,
        // 'status' => 1
      ]);

      $account_default=AccountDefault::first();
      if (empty($account_default->saldo_awal)) {
        return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
      }
      $contact=Contact::find($request->contact_id);
      if (empty($contact->akun_um_supplier)) {
        return Response::json(['message' => '<b>Akun Uang Muka Supplier</b> pada <b>'.$contact->name.'</b> belum ditentukan!'],500);
      } else {
        $akun_um=$contact->akun_um_supplier;
      }

      $journals=Journal::find($payable->journal_id);
      foreach ($journals->details as $value) {
        // cari debet/kredit > 0
        if ($value->debet > 0) {
          $value->update([
            'account_id' => $akun_um,
            'debet' => $request->nominal,
            'description' => $request->description,
          ]);
        } else {
          $value->update([
            'account_id' => $account_default->saldo_awal,
            'credit' => $request->nominal,
            'description' => $request->description,
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
      $jr=UmSupplier::find($id);
      Journal::find($jr->journal_id)->delete();
      DB::commit();

      return Response::json(null);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CekGiro;
use App\Model\Account;
use App\Model\Contact;
use App\Model\Bank;
use App\Model\Company;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\AccountDefault;
use App\Model\TypeTransaction;
use App\Utils\TransactionCode;
use DB;
use Response;

class CekGiroController extends Controller
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
      $data['company']=Company::all();
      $data['bank']=Bank::all();
      $data['contact']=Contact::whereRaw('is_pelanggan = 1 or is_supplier = 1 or is_vendor = 1')->where('vendor_status_approve', 2)->get();
      $data['account']=Account::with('parent')->where('is_base',0)->whereIn('no_cash_bank',[1,2])->get();
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
      $request->validate([
        'giro_no' => 'required|unique:cek_giros,giro_no',
        'company_id' => 'required',
        'date_transaction' => 'required',
        'date_effective' => 'required',
        'type' => 'required',
        'jenis' => 'required',
        // 'bank_id' => 'required',
        'amount' => 'required',
        'penerbit_id' => 'required_if:jenis,1',
        'penerima_id' => 'required_if:jenis,2',
        'reff_no' => 'required_if:is_saldo,1',
      ]);
      DB::beginTransaction();
      $tp=TypeTransaction::where('slug','giro')->first();
      $code = new TransactionCode($request->company_id, $tp->slug);
      $code->setCode();
      $trx_code = $code->getCode();

      $i=CekGiro::create([
        'company_id' => $request->company_id,
        // 'bank_id' => $request->bank_id,
        'code' => $trx_code,
        'penerbit_id' => $request->penerbit_id,
        'penerima_id' => $request->penerima_id,
        'type' => $request->type,
        'jenis' => $request->jenis,
        'date_transaction' => dateDB($request->date_transaction),
        'date_effective' => dateDB($request->date_effective),
        'giro_no' => $request->giro_no,
        'reff_no' => $request->reff_no,
        'amount' => $request->amount,
        'description' => $request->description,
        'account_bank_id' => $request->account_bank_id,
        'is_saldo' => $request->is_saldo,
      ]);

      if ($request->is_saldo==1) {
        $j=Journal::create([
          'company_id' => $request->company_id,
          'type_transaction_id' => $tp->id,
          'date_transaction' => dateDB($request->date_transaction),
          'created_by' => auth()->id(),
          'code' => $trx_code,
          'description' => $request->description,
          'debet' => 0,
          'credit' => 0,
        ]);

        $ad=AccountDefault::first();
        if (empty($ad->saldo_awal)) {
          return Response::json(['message' => 'Akun Default Saldo Awal belum dtentukan!'],500);
        }
        if ($request->jenis==1) {
          if (empty($ad->cek_giro_masuk)) {
            return Response::json(['message' => 'Akun Default Cek/Giro Masuk belum dtentukan!'],500);
          } else {
            $akunG=$ad->cek_giro_masuk;
          }
        } else {
          if (empty($ad->cek_giro_keluar)) {
            return Response::json(['message' => 'Akun Default Cek/Giro Keluar belum dtentukan!'],500);
          } else {
            $akunG=$ad->cek_giro_keluar;
          }
        }

        $cekCC=cekCashCount($request->company_id,$ad->saldo_awal);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }
        // ===============
        // Jurnal Detail
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $ad->saldo_awal,
          'debet' => $request->amount,
          'credit' => 0,
        ]);

        $cekCC=cekCashCount($request->company_id,$akunG);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $akunG,
          'debet' => 0,
          'credit' => $request->amount,
        ]);
        CekGiro::find($i->id)->update([
          'journal_id' => $j->id
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
        //
        $data['company']=Company::all();
        $data['bank']=Bank::all();
        $data['contact']=Contact::whereRaw('is_pelanggan = 1 or is_supplier = 1 or is_vendor = 1')->where('vendor_status_approve', 2)->get();
        $data['item'] = CekGiro::find($id);
        $data['item']->date_effective=dateView($data['item']->date_effective);
        $data['item']->date_transaction=dateView($data['item']->date_transaction);
        $data['account']=Account::with('parent')->where('is_base',0)->whereIn('no_cash_bank',[1,2])->get();
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
      $request->validate([
        'alasan' => 'required_if:isGiro,true|required_if:isCancelGiro,true|required_if:isCancelKliring,true',
      ]);
      $cek_giro = CekGiro::find($id);
      DB::beginTransaction();
      if ($request->isKliring == 'false') {
        $cek_giro->is_empty = 1;
        $cek_giro->save();
      } elseif ($request->isCancelGiro == 'true' || $request->isCancelKliring == 'true') {
        if($request->isCancelGiro == 'true'){
          $cek_giro->is_cancel_empty = 1;
          $cek_giro->is_empty = 0;
          $cek_giro->cancel_empty_reason = $request->alasan;
          $cek_giro->save();
        }else if($request->isCancelKliring == 'true'){
          $cek_giro->is_cancel_kliring = 1;
          $cek_giro->is_kliring = 0;
          $cek_giro->cancel_kliring_reason = $request->alasan;
          $cek_giro->journal_id = null;
          $cek_giro->save();
          $journal = Journal::where('code',$cek_giro->code)->first();
          JournalDetail::where('header_id', $journal->id)->delete();
          $journal->delete();
        }
      } else {
        if ($request->isKliring == 'true') {
          $cek_giro->is_kliring = 1;
          $cek_giro->save();
        }
        // $tp = TypeTransaction::where('slug', 'giro')->first();
        // $code = new TransactionCode($cek_giro->company_id, $tp->slug);
        // $code->setCode();
        // $trx_code = $code->getCode();

        $j = Journal::create([
          'company_id' => $cek_giro->company_id,
          'type_transaction_id' => 9,
          'date_transaction' => dateDB($cek_giro->date_transaction),
          'created_by' => auth()->id(),
          'code' => $cek_giro->code,
          'description' => $cek_giro->description,
          'debet' => 0,
          'credit' => 0,
          'status' => 2
        ]);

        $cek_giro->journal_id = Journal::where('code', $cek_giro->code)->first()->id;
        $cek_giro->save();
        $ad = AccountDefault::first();
        if ($cek_giro->jenis == 1) {
          if (empty($ad->cek_giro_masuk)) {
            return Response::json(['message' => 'Akun Default Cek/Giro Masuk belum dtentukan!'], 500);
          } else {
            $akunG = $ad->cek_giro_masuk;
          }
        } else {
          if (empty($ad->cek_giro_keluar)) {
            return Response::json(['message' => 'Akun Default Cek/Giro Keluar belum dtentukan!'], 500);
          } else {
            $akunG = $ad->cek_giro_keluar;
          }
        }

        $cekCC = cekCashCount($request->company_id, $request->akun);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'], 500);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $cek_giro->account_bank_id,
          'debet' => $cek_giro->amount,
          'credit' => 0,
        ]);

        $cekCC = cekCashCount($cek_giro->company_id, $akunG);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'], 500);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $akunG,
          'debet' => 0,
          'credit' => $cek_giro->amount,
        ]);
        $cek_giro->journal_id = $j->id;
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
      $i=CekGiro::find($id);
      CekGiro::find($id)->delete();
      if (isset($i->journal_id)) {
        Journal::find($i->journal_id)->delete();
      }
      DB::commit();

      return Response::json(null);
    }
}

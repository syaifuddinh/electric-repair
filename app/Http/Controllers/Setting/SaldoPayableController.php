<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\TypeTransaction;
use App\Model\Payable;
use App\Model\PayableDetail;
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

class SaldoPayableController extends Controller
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
        'nominal' => 'required|integer|min:1'
      ]);
      $text="";
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, "saldoAwal");
      $code->setCode();
      $trx_code = $code->getCode();

      $tptrx=TypeTransaction::where('slug','saldoAwal')->first();
      $pay=Payable::create([
        'company_id' => $request->company_id,
        'contact_id' => $request->contact_id,
        'type_transaction_id' => $tptrx->id,
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'date_tempo' => Carbon::parse($request->date_transaction)->addDays($request->jatuh_tempo),
        'description' => $request->description,
        'debet' => 0,
        'credit' => $request->nominal,
      ]);
      $payd=PayableDetail::create([
        'header_id' => $pay->id,
        'type_transaction_id' => $tptrx->id,
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'debet' => 0,
        'credit' => $request->nominal,
      ]);
      $account_default=AccountDefault::first();
      if (empty($account_default->saldo_awal)) {
        return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
      }
      $contact=Contact::find($request->contact_id);
      if (empty($contact->akun_hutang)) {
        if (empty($account_default->hutang)) {
          return Response::json(['message' => 'Default "Akun Hutang" belum ditentukan!'],500);
        }
        $akun_hutang=$account_default->hutang;
        $text.="- Akun Hutang Mengambil dari Default";
      } else {
        $akun_hutang=$contact->akun_hutang;
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
        'status' => 3
      ]);

      $cekCC=cekCashCount($request->company_id,$account_default->saldo_awal);
      if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
      }

      $j_saldo=JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $account_default->saldo_awal,
        'debet' => $request->nominal,
        'credit' => 0,
        'description' => $request->description,
      ]);

      $cekCC=cekCashCount($request->company_id,$akun_hutang);
      if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
      }

      $j_hutang=JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $akun_hutang,
        'debet' => 0,
        'credit' => $request->nominal,
        'description' => $request->description,
      ]);

      Payable::find($pay->id)->update([
        'journal_id' => $j->id,
      ]);
      PayableDetail::find($payd->id)->update([
        'journal_id' => $j->id,
      ]);
      DB::commit();

      return Response::json(['message' => $text]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $dt=Payable::with('contact','company')->where('id', $id)->first();
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
      $data['item']=Payable::with('contact','company')->where('id', $id)->first();
      $data['tempo_day']=Carbon::parse($data['item']->date_transaction)->diffInDays($data['item']->date_tempo);
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
        // dd($request);
        DB::beginTransaction();
        $request->validate([
          'contact_id' => 'required',
          'company_id' => 'required',
          'nominal' => 'required|integer|min:1'
        ]);
        $text="";
        Payable::find($id)->update([
          // 'company_id' => $request->company_id,
          'contact_id' => $request->contact_id,
          // 'type_transaction_id' => $tptrx->id,
          // 'created_by' => auth()->id(),
          // 'code' => $trx_code,
          'date_transaction' => dateDB($request->date_transaction),
          'date_tempo' => Carbon::parse($request->date_transaction)->addDays($request->jatuh_tempo),
          'description' => $request->description,
          // 'debet' => 0,
          'credit' => $request->nominal,
        ]);
        $payable=Payable::find($id);
        $payd=PayableDetail::where('header_id', $id)->where('type_transaction_id', $payable->type_transaction_id)->update([
          // 'header_id' => $pay->id,
          // 'type_transaction_id' => $tptrx->id,
          // 'code' => $trx_code,
          'date_transaction' => dateDB($request->date_transaction),
          // 'debet' => 0,
          'credit' => $request->nominal,
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
        $journals=Journal::find($payable->journal_id);
        // cari jurnal lagi
        $account_default=AccountDefault::first();
        if (empty($account_default->saldo_awal)) {
          return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
        }
        $contact=Contact::find($request->contact_id);
        if (empty($contact->akun_hutang)) {
          if (empty($account_default->hutang)) {
            return Response::json(['message' => 'Default "Akun Hutang" belum ditentukan!'],500);
          }
          $akun_hutang=$account_default->hutang;
          $text.="- Akun Hutang Mengambil dari Default";
        } else {
          $akun_hutang=$contact->akun_hutang;
        }
        // end cari jurnal
        foreach ($journals->details as $value) {
          // cari debet/kredit > 0
          if ($value->debet > 0) {
            $value->update([
              'account_id' => $account_default->saldo_awal,
              'debet' => $request->nominal,
              'description' => $request->description,
            ]);
          } else {
            $value->update([
              'account_id' => $akun_hutang,
              'credit' => $request->nominal,
              'description' => $request->description,
            ]);
          }
        }

        DB::commit();

        return Response::json(['message' => $text]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      /*
      table need to be delete :
      payable
      PayableDetail
      journal
      JournalDetail saldo
      JournalDetail hutang
      */

      DB::beginTransaction();
      try {
        $payable = Payable::find($id);

        $payable->payableDetails()->delete();

        $journal = $payable->journal;
        $journal->details()->delete();

        $payable->delete();
        $journal->delete();

      } catch (\Exception $e) {
        DB::rollback();
        return $e->getMessage();
      }

      DB::commit();
    }
}

<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\TypeTransaction;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
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

class SaldoReceivableController extends Controller
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
      $data['contact']=Contact::whereRaw("is_pelanggan=1")->get();

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
       $text="";
       $request->validate([
         'contact_id' => 'required',
         'company_id' => 'required',
         'nominal' => 'required|integer|min:1'
       ], [
          'contact_id.required' => 'Customer tidak boleh kosong'
       ]);

       DB::beginTransaction();
       $code = new TransactionCode($request->company_id, "saldoAwal");
       $code->setCode();
       $trx_code = $code->getCode();

       //return Response::json(['nominal'=>$request->nominal]);

       $tptrx=TypeTransaction::where('slug','saldoAwal')->first();
       $pay=Receivable::create([
         'company_id' => $request->company_id,
         'contact_id' => $request->contact_id,
         'type_transaction_id' => $tptrx->id,
         'created_by' => auth()->id(),
         'code' => $trx_code,
         'date_transaction' => dateDB($request->date_transaction),
         'date_tempo' => Carbon::parse($request->date_transaction)->addDays($request->jatuh_tempo),
         'description' => $request->description,
         'debet' => $request->nominal,
         'credit' => 0,
       ]);

       ReceivableDetail::where('header_id', $pay->id)->delete();
       $payd=ReceivableDetail::create([
         'header_id' => $pay->id,
         'type_transaction_id' => $tptrx->id,
         'code' => $trx_code,
         'date_transaction' => dateDB($request->date_transaction),
         'debet' => $request->nominal,
         'credit' => 0,
       ]);

       $account_default=AccountDefault::first();
       if (empty($account_default->saldo_awal)) {
         return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
       }
       
       $contact=Contact::find($request->contact_id); 
       if (empty($contact->akun_piutang)) {
         if (empty($account_default->piutang)) {
           return Response::json(['message' => 'Default "Akun Piutang" belum ditentukan!'],500);
         }
         $akun_piutang=$account_default->piutang;
         $text.="- Akun Piutang Mengambil dari Default";
       } else {
         $akun_piutang=$contact->akun_piutang;
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
         'debet' => 0,
         'credit' => $request->nominal,
         'description' => $request->description,
       ]);

       $cekCC=cekCashCount($request->company_id,$akun_piutang);
       if ($cekCC) {
         return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
       }

       $j_piutang=JournalDetail::create([
         'header_id' => $j->id,
         'account_id' => $akun_piutang,
         'debet' => $request->nominal,
         'credit' => 0,
         'description' => $request->description,
       ]);

       Receivable::find($pay->id)->update([
         'journal_id' => $j->id,
       ]);

       ReceivableDetail::find($payd->id)->update([
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
      $dt=Receivable::with('contact','company')->where('id', $id)->first();
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
      $data['contact']=Contact::whereRaw("is_pelanggan=1")->get();
      $data['item']=Receivable::with('contact','company')->where('id', $id)->first();
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
         $text="";
         DB::beginTransaction();
         $request->validate([
           'contact_id' => 'required',
           'company_id' => 'required',
           'nominal' => 'required|integer|min:1'
         ]);
         Receivable::find($id)->update([
           // 'company_id' => $request->company_id,
           'contact_id' => $request->contact_id,
           // 'type_transaction_id' => $tptrx->id,
           // 'created_by' => auth()->id(),
           // 'code' => $trx_code,
           'date_transaction' => dateDB($request->date_transaction),
           'date_tempo' => Carbon::parse($request->date_transaction)->addDays($request->jatuh_tempo),
           'description' => $request->description,
           'debet' => $request->nominal,
           // 'credit' => 0,
         ]);
         $receivable=Receivable::find($id);
         $payd=ReceivableDetail::where('header_id', $id)->where('type_transaction_id', $receivable->type_transaction_id)->update([
           // 'header_id' => $pay->id,
           // 'type_transaction_id' => $tptrx->id,
           // 'code' => $trx_code,
           'date_transaction' => dateDB($request->date_transaction),
           'debet' => $request->nominal,
           // 'credit' => 0,
         ]);
         $j=Journal::find($receivable->journal_id)->update([
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
         if (empty($contact->akun_piutang)) {
           if (empty($account_default->piutang)) {
             return Response::json(['message' => 'Default "Akun Piutang" belum ditentukan!'],500);
           }
           $akun_piutang=$account_default->piutang;
           $text.="- Akun Hutang Mengambil dari Default";
         } else {
           $akun_piutang=$contact->akun_piutang;
         }

         $journals=Journal::find($receivable->journal_id);
         foreach ($journals->details as $value) {
           // cari debet/kredit > 0
           if ($value->debet > 0) {
             $value->update([
               'account_id' => $akun_piutang,
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
      receivable
      receivableDetail
      journal
      JournalDetail saldo
      JournalDetail piutang
      */

      DB::beginTransaction();
      try {
        $receivable = Receivable::find($id);

        $receivable->receivableDetails()->delete();

        $journal = $receivable->journal;
        $journal->details()->delete();

        $receivable->delete();
        $journal->delete();

        DB::commit();
      } catch (\Exception $e) {
        DB::rollback();
        return Response::json(['message' => $e], 500);
      }

    }
}

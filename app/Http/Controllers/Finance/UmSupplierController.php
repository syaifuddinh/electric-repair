<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\TypeTransaction;
use App\Model\UmSupplier;
use App\Model\UmSupplierDetail;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Contact;
use App\Model\Company;
use App\Model\UmSupplierPaid;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\CekGiro;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Utils\TransactionCode;
use Response;
use DB;
use Carbon\Carbon;

class UmSupplierController extends Controller
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
      $data['supplier']=Contact::whereRaw("is_supplier=1 OR is_vendor=1")->where('vendor_status_approve', 2)->get();
      $data['cash_account']=Account::where('is_base',0)->whereIn('no_cash_bank',[1,2])->get();

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
        'amount' => 'required|integer',
        'contact_id' => 'required',
      ]);

      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, "depositSupplier");
      $code->setCode();
      $trx_code = $code->getCode();

      $contact=Contact::find($request->contact_id);
      $tptrx=TypeTransaction::where('slug','depositSupplier')->first();
      $um=UmSupplier::create([
        'company_id' => $request->company_id,
        'contact_id' => $request->contact_id,
        'type_transaction_id' => $tptrx->id,
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'description' => $request->description,
        'debet' => $request->amount,
        'credit' => 0,
      ]);

      $d=UmSupplierDetail::create([
        'header_id' => $um->id,
        'type_transaction_id' => $tptrx->id,
        'code' => $trx_code,
        'date_transaction' => dateDB($request->date_transaction),
        'debet' => $request->amount,
        'credit' => 0,
        'description' => $request->description
      ]);
      $cek_giro_amount=0;
      $cash_amount=0;
      $account_default=AccountDefault::first();
      $akun_kas_list=[];
      $akun_kas_amount=[];
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $acc=Account::find($value['cash_account_id']);
        UmSupplierPaid::create([
          'header_id' => $um->id,
          'type_paid' => $value['type'],
          'type_transaction_id' => $tptrx->id,
          'reff_id' => ($value['type']==1?$value['cash_account_id']:$value['cek_giro_id']),
          'code' => ($value['type']==1?$acc->name:$value['cek_giro_name']),
          'amount' => $value['amount']
        ]);

        if ($value['type']==2) {
          //jika menggunakan cek/giro
          CekGiro::find($value['cek_giro_id'])->update([
            'is_used' => 1,
            'reff_no' => $trx_code
          ]);
          $cek_giro_amount+=$value['amount'];
        } else {
          if (!in_array($value['cash_account_id'],$akun_kas_list)) {
            $akun_kas_list[]=$value['cash_account_id'];
          }
          $akun_kas_amount[$value['cash_account_id']][]=$value['amount'];
          $cash_amount+=$value['amount'];
          //jika menggunakan kas
        }
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

      if (empty($contact->akun_um_supplier)) {
        return Response::json(['message' => 'Akun Uang Muka Supplier belum ditentukan di master kontak'],500);
      }

      $cekCC=cekCashCount($request->company_id,$contact->akun_um_supplier);
      if ($cekCC) {
        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
      }


      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $contact->akun_um_supplier,
        'debet' => $request->amount,
        'credit' => 0,
      ]);
      if ($cek_giro_amount>0) {
        if (empty($account_default->cek_giro_keluar)) {
          return Response::json(['message' => 'Akun Default Giro Keluar belum ditentukan'],500);
        }

        $cekCC=cekCashCount($request->company_id,$account_default->cek_giro_keluar);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $account_default->cek_giro_keluar,
          'debet' => 0,
          'credit' => $cek_giro_amount,
        ]);
      }
      if ($cash_amount>0) {
        foreach ($akun_kas_list as $val) {

          $cekCC=cekCashCount($request->company_id,$val);
          if ($cekCC) {
            return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
          }

          JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $val,
            'debet' => 0,
            'credit' => array_sum($akun_kas_amount[$val]),
          ]);
          $acc=Account::find($val);
          $ct=CashTransaction::create([
            'company_id' => $request->company_id,
            'type_transaction_id' => $tptrx->id,
            'code' => $trx_code,
            'reff' => $trx_code,
            'jenis' => 2,
            'type' => $acc->no_cash_bank,
            'description' => $request->description,
            'total' => array_sum($akun_kas_amount[$val]),
            'account_id' => $val,
            'date_transaction' => dateDB($request->date_transaction),
            'status_cost' => 3
          ]);
          CashTransactionDetail::create([
            'header_id' => $ct->id,
            'account_id' => $contact->akun_um_supplier,
            'contact_id' => $contact->id,
            'amount' => array_sum($akun_kas_amount[$val]),
          ]);
        }
      }

      if ($request->lebih_bayar>0) {
        if (empty($account_default->lebih_bayar_piutang)) {
          return Response::json(['message' => 'Akun Default Lebih Bayar Piutang belum ditentukan'],500);
        }

        $cekCC=cekCashCount($request->company_id,$account_default->lebih_bayar_piutang);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $account_default->lebih_bayar_piutang,
          'debet' => $request->lebih_bayar,
          'credit' => 0,
        ]);
      }

      UmSupplier::find($um->id)->update(['journal_id' => $j->id]);
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
        $data['item'] = UmSupplier::with('company','contact')
                                  ->where('id', $id)->first();
        $data['paid'] = UmSupplierPaid::where('header_id', $id)->get();
        $data['cash_account'] = Account::where('is_base',0)
                                       ->whereIn('no_cash_bank',[1,2])
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
    private function isGiro($type)
    {
      return $type == 2;
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      $umSupplier = UmSupplier::find($id);
      $umSupplierPaids = $umSupplier->umSupplierPaids;
      $journal = $umSupplier->journal;

      // loop & cek if giro
      foreach ($umSupplierPaids as $umSupplierPaid)
      {
        if($this->isGiro($umSupplierPaid->type_paid))
        {
          // giro tidak jadi dipakai
          CekGiro::find($umSupplierPaid->reff_id)->update([
            'is_used' => 0,
            'reff_no' => $umSupplier->code
          ]);
        }
      }

      // deletes
      $umSupplier->umSupplierDetail()->delete();
      $umSupplier->umSupplierPaids()->delete();
      $umSupplier->delete();

      $journal->details()->delete();
      $journal->delete();

      return Response::json([], 204);
    }

    public function returnSisa(Request $request, int $id)
    {
        $um = UMSupplier::find($id);
        $company_id = $um->company_id;
        $contact = Contact::find($um->contact_id);
        $type_transaction = TypeTransaction::where('slug', 'depositSupplier')
            ->first();
        $code = new TransactionCode($company_id, 'depositSupplier');
        $code->setCode();
        $trx_code = $code->getCode();

        $sisa = $request->sisa;
        $transaction_date = dateDB($request->transaction_date);

        if($sisa <= 0 || ($um->debet < $sisa + $um->credit))
            return Response::json("OK", 200);

        UMSupplierDetail::create([
            'header_id' => $um->id,
            'type_transaction_id' => $type_transaction->id,
            'code' => $trx_code,
            'date_transaction' => $transaction_date,
            'debet' => 0,
            'credit' => $sisa,
            'description' => "Pengembalian sisa deposit"
        ]);

        $um->update(["credit" => $sisa + $um->credit]);

        $j = Journal::create([
            'company_id' => $company_id,
            'type_transaction_id' => $type_transaction->id,
            'date_transaction' => $transaction_date,
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'description' => 'Pengembalian sisa deposit',
            'debet' => 0,
            'credit' => 0,
        ]);

        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $contact->akun_um_supplier,
            // 'cash_category_id' => $cid,
            'debet' => 0,
            'credit' => $sisa,
        ]);

        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $request->cash_account_id,
            // 'cash_category_id' => $cid,
            'debet' => $sisa,
            'credit' => 0,
        ]);

        return Response::json("OK", 200);
    }
}

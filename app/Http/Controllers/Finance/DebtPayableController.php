<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Debt;
use App\Model\DebtDetail;
use App\Model\DebtPayment;
use App\Model\InvoiceVendor;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\CekGiro;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\UmSupplier;
use App\Utils\TransactionCode;
use DB;
use Response;

class DebtPayableController extends Controller
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
      $data['vendor'] = Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2 or is_supplier = 1")->select('id','name','company_id','address')->get();;
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 06-03-2020
      Description : Menyimpan pembayaran hutang
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
      $request->validate([
        'company_id' => 'required',
        'supplier_id' => 'required',
        'date_transaction' => 'required',
        'total' => 'required',
        'overpayment' => 'required',
      ]);
      DB::beginTransaction();

      $leftover = 0;
      foreach ($request->detail as $key => $value) {
          $leftover += $value['leftover'];
      }

      if($leftover != 0) {
          if(!$request->filled('akun_selisih')) {
              return Response::json(['message' => 'Akun selisih tidak boleh kosong'], 421);
          }
      }

      $code = new TransactionCode($request->company_id, 'debtPayable');
      $code->setCode();
      $trx_code = $code->getCode();
      $b=Debt::create([
        'company_id' => $request->company_id,
        'create_by' => auth()->id(),
        'date_request' => dateDB($request->date_transaction),
        'akun_selisih' => $request->akun_selisih,
        'total' => $request->total,
        'description' => $request->description,
        'code' => $trx_code,
      ]);

      $jurnal=[
          'company_id' => $request->company_id,
          'date_transaction' => date('Y-m-d'),
          'created_by' => auth()->id(),
          'code' => $trx_code,
          'description' => $request->description,
          'debet' => 0,
          'credit' => 0,
          'status' => 2,
          'type_transaction_id' => 29
      ];

      $journal_id = DB::table('journals')
      ->insertGetId($jurnal);

      $account_default = DB::table('account_defaults')->first();
      foreach ($request->detail as $key => $value) {
        if(empty($value)) {
            continue;
        }
        $r=Payable::find($value['payable_id']);
        $invoiceVendor = InvoiceVendor::wherePayableId($r->id ?? null);
        if($invoiceVendor->first() != null) {
          $invoiceVendor->update([
              'status_approve' =>  4
          ]);
        }
        $rd=PayableDetail::create([
          'header_id' => $r->id,
          'type_transaction_id' => 29, // debt payable
          'code' => $trx_code,
          'date_transaction' => dateDB($request->date_transaction),
          'relation_id' => $b->id,
          'debet' => $value['debt'],
          'is_journal' => 0
        ]);

        DebtDetail::create([
          'header_id' => $b->id,
          'type_transaction_id' => $r->type_transaction_id,
          'code' => $r->code,
          'payable_id' => $value['payable_id'],
          'payable_detail_id' => $rd->id,
          'create_by' => auth()->id(),
          'debt' => $value['debt'],
          'leftover' => $value['leftover'],
          'total_debt' => $value['total'],
          'description' => $value['description']??'-',
        ]);

        $payable = DB::table('payables')
        ->join('manifest_costs', 'manifest_costs.id', 'payables.relation_id')
        ->join('cost_types', 'cost_types.id', 'manifest_costs.cost_type_id')
        ->where('payables.id', $value['payable_id'])
        ->where('payables.type_transaction_id',54)
        ->first() ?? DB::table('payables')
        ->join('job_order_costs', 'job_order_costs.id', 'payables.relation_id')
        ->join('cost_types', 'cost_types.id', 'job_order_costs.cost_type_id')
        ->where('payables.id', $value['payable_id'])
        ->where('payables.type_transaction_id',50)
        ->first();

        if($payable != null) {
            $akun_uang_muka = $payable->akun_uang_muka;
            $akun_kas_hutang = $payable->akun_kas_hutang;
        } else {
            $akun_uang_muka = $account_default->saldo_awal;
            if(!$akun_uang_muka) {
                throw new Exception('Akun saldo awal belum di set');
            }
            $akun_kas_hutang = $account_default->hutang;
            if(!$akun_kas_hutang) {
                throw new Exception('Akun hutang belum di set');
            }
        }

        JournalDetail::create([
          'header_id' => $journal_id,
          'account_id' => $akun_uang_muka,
          'debet' => $value['total'],
          'credit' => 0,
          'description' => $value['description'] ?? ''
        ]);

        JournalDetail::create([
          'header_id' => $journal_id,
          'account_id' => $akun_kas_hutang,
          'debet' => 0,
          'credit' => $value['debt'],
          'description' => $value['description'] ?? ''
        ]);

      }

      if($leftover < 0) {
          if(!$request->filled('akun_selisih')) {
              return Response::json(['message' => 'Akun selisih tidak boleh kosong'], 421);
          }
          JournalDetail::create([
          'header_id' => $journal_id,
          'account_id' => $request->akun_selisih,
          'debet' => -$leftover,
          'credit' => 0,
          'description' => $request->description ?? ''
        ]);
      } else if($leftover > 0) {
          if(!$request->filled('akun_selisih')) {
              return Response::json(['message' => 'Akun selisih tidak boleh kosong'], 421);
          }
          JournalDetail::create([
          'header_id' => $journal_id,
          'account_id' => $request->akun_selisih,
          'debet' => 0,
          'credit' => $leftover,
          'description' => $request->description ?? ''
        ]);
      } 
      

      DB::commit();

      return Response::json(null);
    }

   /*
      Date : 02-03-2020
      Description : Menampilkan detail pembayaran hutang
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
      $data['item']=Debt::with('company')
      ->leftJoin('accounts', 'accounts.id', 'debts.akun_selisih')
      ->where('debts.id', $id)
      ->select('debts.*', 'accounts.name AS akun_selisih_name')
      ->first();

      $data['detail']=DebtDetail::with('type_transaction:id,name','payable.contact:id,name,address')
      ->where('header_id', $id)
      ->get();

      $data['payment']=DebtPayment::with('cash_account','cek_giro')->where('header_id', $id)->whereNull('reff')->get();
      $data['paymentbp']=DebtPayment::with('cash_account','cek_giro')->where('header_id', $id)->whereNotNull('reff')->get();
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
      $data['item']=Debt::with('company')->where('id', $id)->first();
      $data['detail']=DebtDetail::with('type_transaction','payable.contact')->where('header_id', $id)->get();

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
        'supplier_id' => 'required',
        'date_transaction' => 'required',
        'total' => 'required',
        'overpayment' => 'required',
      ]);
      DB::beginTransaction();

      $b=Debt::find($id);
      $b->update([
        'company_id' => $request->company_id,
        'date_request' => dateDB($request->date_transaction),
        'total' => $request->total,
        'description' => $request->description
      ]);

      // delete
      $this->deleteDebtDetails($b->id);
      if (empty($request->detail)) {
        return Response::json(['message' => 'tidak ada tagihan yang di pilih.'], 422);
      }

      foreach ($request->detail as $key => $value) {
        if (empty($value['payable_id'])) {
          continue;
        }
        $r=Payable::find($value['payable_id']);
        $rd=PayableDetail::create([
          'header_id' => $r->id,
          'type_transaction_id' => 29, // debt payable
          'code' => $b->code,
          'date_transaction' => dateDB($request->date_transaction),
          'relation_id' => $b->id,
          'debet' => $value['debt'],
          'is_journal' => 0
        ]);

        DebtDetail::create([
          'header_id' => $b->id,
          'type_transaction_id' => $r->type_transaction_id,
          'code' => $r->code,
          'payable_id' => $value['payable_id'],
          'payable_detail_id' => $rd->id,
          'create_by' => auth()->id(),
          'debt' => $value['debt'],
          'leftover' => $value['leftover'],
          'total_debt' => $value['total'],
          'description' => $value['description']??'-',
        ]);
      }
      DB::commit();

      return Response::json(null);
    }
    public function destroyDebtDetailById($id)
    {
      try {
        //delete tagihan by id
        $debtDetail = DebtDetail::find($id);
        $payableDetail = $debtDetail->payableDetail;

        // reset value debet in payable
        $payable = $debtDetail->payable;
        $payable->debet = $payable->debet - $payableDetail->debet;
        $payable->save();

        $debtDetail->delete();
        $payableDetail->delete();
      } catch (\Exception $e) {
        return Response::json([], 500);
      }

      return Response::json([], 204);
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
       try {
         $this->deleteDebtDetails($id);
         Debt::find($id)->delete();
       } catch (\Exception $e) {
         DB::rollback();
         return Response::json([$e->getMessage()], 500);
       }
       DB::commit();
       return Response::json([], 204);
     }

     private function deleteDebtDetails($id)
     {
       $debt = Debt::find($id);
       $debtDetails = $debt->debtDetails;

       foreach ($debtDetails as $debtDetail) {
         $payableDetail = $debtDetail->payableDetail;

         // reset nilai debet di payable jadi 0
         $payable = $debtDetail->payable;
         $payable->debet = $payable->debet - $payableDetail->debet;
         $payable->save();

         $debtDetail->delete();
         $payableDetail->delete();
       }
     }

    public function cari_supplier_list($id) {
        $data = Contact::whereRaw("is_supplier = 1 or is_vendor = 1 and vendor_status_approve = 2")
            ->select('id','name','address')
            ->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function payment($id)
    {
      $data['item']=Debt::with('company')->where('id', $id)->first();
      $data['detail']=DebtDetail::with(['type_transaction', 'payable.contact'])->where('header_id', $id)->get();
      $data['account']=Account::with('parent')->where('is_base',0)->select('id','code','name','no_cash_bank')->get();
      //by andre 7 agustus 2019
      $data['accountall']=DB::table('accounts')->where('is_base',0)->select('id','deep','code','name','no_cash_bank')->get();
      // $data['accountall']=Account::with('parent')->select('id','code','name','no_cash_bank')->get();
      //end by andre
      $data['cek_giro']=CekGiro::where('is_used',0)->where('jenis', 2)->where('penerima_id', $data['detail'][0]->payable->contact_id)->get();
      $data['payable']=Payable::leftJoin('contacts','contacts.id','=','payables.contact_id')->whereRaw('(credit-debet) > 0')->where('payables.contact_id', $data['detail'][0]->payable->contact_id)->select('payables.id','payables.code','contacts.name',DB::raw('credit-debet as total'))->get();
      // $data['uang_muka']=UmCustomer::where('contact_id', $data['item']->customer_id)->select('id','code',DB::raw('credit-debet as total'))->whereRaw('(credit-debet) > 0')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function validasiBP(Request $request, $id)
    {
      $bp=[];
      $nilaibp=[];
      $todaydate = date('Y-m-d');
      
      // foreach ($request->paymentbp as $key => $value) {
      //   if (empty($value)) {
      //     continue;
      //   }
      //   if ($value['bp_cash_account_id']??null) {
      //     $bp[]=$value['bp_cash_account_id'];
      //     $nilaibp[]=$value['totalbp'];
      //   }
      // }

      // foreach ($bp as $key => $value) {
      //     JournalDetail::create([
      //     'header_id' => $j->id,
      //     'account_id' => $value,
      //     // 'cash_category_id' => $cid,
      //     'debet' => 0,
      //     'credit' => $nilaibp[$key],
      //   ]);
      //   }

      $cekDefault=AccountDefault::first();
      if (empty($cekDefault->bukti_potong_hutang)) {
        return Response::json(['message' => 'Akun Default Hutang Belum Ditentukan!'],500);
      }
      


      $idutkbill=DB::table('debt_payments')
            ->select('header_id')
            ->where('id', $request->id)
            ->get();

      foreach ($idutkbill as $target) {
              
               $tabelbill=DB::table('debts')
                ->select('company_id')
                ->where('id', $target->header_id)
                ->get();

                 foreach ($tabelbill as $targetdlm) {
                  $code = new TransactionCode($targetdlm->company_id, 'billReceivablePayment');
                  $code->setCode();
                  $trx_code = $code->getCode();
                  $j=Journal::create([
                    'company_id' => $targetdlm->company_id,
                    'type_transaction_id' => 31,
                    'date_transaction' => $todaydate,
                    'created_by' => auth()->id(),
                    'code' => $trx_code,
                    'description' => 'Pembayaran Tagihan Hutang - ',
                    'debet' => 0,
                    'credit' => 0,
                  ]);

                  JournalDetail::create([
                    'header_id' => $j->id,
                    'account_id' => $cekDefault->bukti_potong,
                    // 'cash_category_id' => $cid,
                    'debet' => 0,
                    'credit' => $request->total,
                  ]);

                  JournalDetail::create([
                  'header_id' => $j->id,
                  'account_id' => $request->cash_account_id,
                  // 'cash_category_id' => $cid,
                  'debet' => $request->total,
                  'credit' => 0,
                ]);

                 };


                

                
            };

      

      // JournalDetail::create([
      //     'header_id' => $request->journal_id,
      //     'account_id' => $request->cash_account_id,
      //     // 'cash_category_id' => $cid,
      //     'debet' => 0,
      //     'credit' => $request->total,
      //   ]);

      

      DB::table('debt_payments')
            ->where('id', $request->id)
            ->update(['valid' => 1]);


       foreach ($idutkbill as $target) {
               DB::table('debts')
                ->where('id', $target->header_id)
                ->update(['status' => 3]);
            };
      
    }

    public function uploadBP(Request $request,$id){
    $request->validate([
        'file' => 'required|mimetypes:image/jpeg,image/png,application/pdf',
      ],[
        'file.mimetypes' => 'File Harus Berupa Gambar atau PDF!',
        'file.required' => 'File belum ada!'
      ]);
    

    $file=$request->file('file');
    $file_name="BP_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();

    DB::table('debt_payments')
            ->where('id', $id)
            ->update(['filename' => $file_name]);

    $file->move(public_path('files'),$file_name);
  }

    public function store_payment(Request $request, $id)
    {
      DB::beginTransaction();
      $debt=Debt::find($id);

      $code = new TransactionCode($debt->company_id, 'debtPayablePayment');
      $code->setCode();
      $trx_code = $code->getCode();

      //by andre 7 agustus 2019
      $totalPaymentBp=$request->total_paymentbp;
      $cash_account_krg=$request->cash_account_id_krg;
      $statusbill=2;
      //end by andre

      Debt::find($id)->update([
        'status' => 2,
        'code_receive' => $trx_code,
        'date_receive' => dateDB($request->date_receive),
      ]);

      //hitung Kas/Bank-Cek/giroPayable::
      $giro=[];
      $kas=[];

      //by andre 7 agustus 2019
      $bp=[];
      $nilaibp=[];
      $bpA=0;
      $totalPayment=$request->total_payment;
      //end by andre

      $nilaiKas=[];
      $giroA=0;
      $kasA=0;

      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        //jika ada payable id
        if ($value['payable_detail_id']??null) {
          PayableDetail::find($value['payable_detail_id'])->update([
            'debet' => $value['debt']
          ]);
        } else {
          if ($value['payable_id']??null) {
            //simpan di tabel piutang detail
            $rds=PayableDetail::create([
              'header_id' => $value['payable_id'],
              'type_transaction_id' => 29, //debt payable
              'code' => $trx_code,
              'date_transaction' => dateDB($request->date_receive),
              'relation_id' => $id,
              'debet' => $value['debt'],
              'is_journal' => 0
            ]);
          }
        }
        //jika ada id detail
        if ($value['id']??false) {
          DebtDetail::where('id', $value['id']??0)->update([
            'debt' => $value['debt'],
            'description' => $value['description']??null,
          ]);
        } else {
          DebtDetail::create([
            'header_id' => $id,
            'type_transaction_id' => 29,
            'code' => $trx_code,
            'payable_id' => $value['payable_id']??null,
            'payable_detail_id' => $rds->id??null,
            'um_supplier_id' => $value['um_supplier_id']??null,
            'create_by' => auth()->id(),
            'debt' => $value['debt'],
            'leftover' => $value['leftover'],
            'total_debt' => $value['total_debt'],
            'description' => @$value['description'],
            'jenis' => $value['jenis']??1,
            'account_id' => $value['account_id']??null,
          ]);
        }
        //jika ada cndn
        // if ($value['account_id']??null) {
        //   if (in_array($value['no_cash_bank'],[1,2])) {
        //     $i=CashTransaction::create([
        //       'company_id' => $debt->company_id,
        //       'type_transaction_id' => 31, // pembayaran tagihan piutang
        //       // 'code' => $trx_code,
        //       // 'journal_id' => $j->id,
        //       'reff' => $debt->code,
        //       'jenis' => $value['jenis'],
        //       'type' => $value['no_cash_bank'],
        //       'description' => 'Pembayaran Tagihan Piutang - '.$debt->code,
        //       'total' => $value['debt'],
        //       'account_id' => $value['account_id'],
        //       'date_transaction' => dateDB($request->date_receive),
        //       'status_cost' => 3,
        //       'created_by' => auth()->id()
        //     ]);
        //
        //     CashTransactionDetail::create([
        //       'header_id' => $i->id,
        //       'account_id' => $value['cash_account_id'],
        //       'contact_id' => $debt->customer_id,
        //       'amount' => $value['total'],
        //       'description' => @$value['description'],
        //       'jenis' => 1
        //     ]);
        //
        //   }
        // }
      }
      if (empty($request->payment_detail)) {
        return Response::json(['message' => 'cara bayar belum diisi'], 422);
      }
      foreach ($request->payment_detail as $key => $value) {
        // dd();
        if ($value['cek_giro_id']??null) {
          $giro[]=$value['cek_giro_id'];
          $giroA+=$value['total'];
        }
        if ($value['cash_account_id']??null) {
          $kas[]=$value['cash_account_id'];
          $nilaiKas[]=$value['total'];
          $kasA+=$value['total'];
        }
      }

      foreach ($request->paymentbp_detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        if ($value['bp_cash_account_id']??null) {
          $bp[]=$value['bp_cash_account_id'];
          $nilaibp[]=$value['totalbp'];
          $bpA+=$value['totalbp'];
        }
      }
      //menjurnal dahulu
      $j=Journal::create([
        'company_id' => $debt->company_id,
        'type_transaction_id' => 31,
        'date_transaction' => dateDB($request->date_receive),
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'description' => 'Pembayaran Tagihan Piutang - '.$debt->code,
        'debet' => 0,
        'credit' => 0,
      ]);

      //jurnal detail kas
      foreach ($kas as $key => $value) {
        $cekCC=cekCashCount($debt->company_id,$value);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value,
          // 'cash_category_id' => $cid,
          'debet' => 0,
          'credit' => $nilaiKas[$key],
        ]);
      }

      //jurnal hutang
      $cekDefault=AccountDefault::first();
      if (empty($cekDefault->hutang)) {
        return Response::json(['message' => 'Akun Default Hutang Belum Ditentukan!'],500);
      }
      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $cekDefault->hutang,
        // 'cash_category_id' => $cid,
        'debet' => $request->total_tagih,
        'credit' => 0,
      ]);

      //jurnal detail giro / jika ada
      if (count($giro)>0) {
        if (empty($cekDefault->cek_giro_masuk)) {
          return Response::json(['message' => 'Akun Default Penjualan Belum Ditentukan!'],500);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $cekDefault->cek_giro_masuk,
          // 'cash_category_id' => $cid,
          'debet' => 0,
          'credit' => $giroA,
        ]);
      }

      //by andre 7 agustus 2019
      //jurnal bukti potong / jika ada
      if ($totalPaymentBp>0) {
        // if (empty($cekDefault->cek_giro_masuk)) {
        //   return Response::json(['message' => 'Akun Default Penjualan Belum Ditentukan!'],500);
        // }

        foreach ($bp as $key => $value) {
          JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value,
          // 'cash_category_id' => $cid,
          'debet' => 0,
          'credit' => $nilaibp[$key],
        ]);
        }
        
      }
      //end by andre


      //versi sebelumnya cuma ada lebih bayar, comment by andre 7 agustus 2019 
      // //jika ada lebih bayar
      // if ($request->leftover_payment>0) {
      //   if (empty($cekDefault->lebih_bayar_hutang)) {
      //     return Response::json(['message' => 'Akun Default Lebih Bayar Hutang Belum Ditentukan!'],500);
      //   }
      //   JournalDetail::create([
      //     'header_id' => $j->id,
      //     'account_id' => $cekDefault->lebih_bayar_hutang,
      //     // 'cash_category_id' => $cid,
      //     'debet' => $request->leftover_payment,
      //     'credit' => 0,
      //   ]);
      // }
      // end by andre


      //jika ada lebih bayar(versi baru,made by andre 7 agustus 2019)
      $testkrg=$request->total_tagih-$request->total_payment;
      if ($testkrg>0) {
        if (empty($cekDefault->lebih_bayar_piutang)) {
          return Response::json(['message' => 'Akun Default Lebih Bayar Piutang Belum Ditentukan!'],500);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $cash_account_krg,
          'debet' => 0,
          'credit' => $request->leftover_payment,
        ]);
      }


      if ($testkrg<0) {
        if (empty($cekDefault->lebih_bayar_piutang)) {
          return Response::json(['message' => 'Akun Default Lebih Bayar Piutang Belum Ditentukan!'],500);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $cash_account_krg,
          'debet' => $request->leftover_payment,
          'credit' => 0,
        ]);
      }
      //end by andre

      //simpan debt payment
      foreach ($request->payment_detail as $key => $value) {
        if(is_null($value))
            continue;

        DebtPayment::create([
          'header_id' => $id,
          'journal_id' => $j->id,
          'create_by' => auth()->id(),
          'payment_type' => $value['payment_type'],
          'total' => $value['total'],
          'description' => $value['description']??null,
          'cash_account_id' => $value['cash_account_id']??null,
          'cek_giro_id' => $value['cek_giro_id']??null,
        ]);

        if ($value['cash_account_id']??null) {
          //jika pakai kas simpan di transaksi kas
          $acc=Account::find($value['cash_account_id']);
          $i=CashTransaction::create([
            'company_id' => $debt->company_id,
            'type_transaction_id' => 31, // pembayaran tagihan piutang
            'code' => $trx_code,
            'journal_id' => $j->id,
            'reff' => $debt->code,
            'jenis' => 2,
            'type' => $acc->no_cash_bank,
            'description' => 'Pembayaran Tagihan Hutang - '.$debt->code,
            'total' => $value['total'],
            'account_id' => $acc->id,
            'date_transaction' => dateDB($request->date_receive),
            'status_cost' => 3,
            'created_by' => auth()->id()
          ]);

          $acd=AccountDefault::first();
          if (empty($acd->hutang)) {
            return Response::json(['message' => 'Akun Default Hutang Belum Ditentukan!'],500);
          }

          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $acd->hutang,
            'contact_id' => $debt->debtDetails()->first()->payable->contact_id,
            'amount' => $value['total'],
            'description' => @$value['description'],
            'jenis' => 1
          ]);
        }

        //jika ada giro
        if ($value['cek_giro_id']??null) {
          $cg=CekGiro::find($value['cek_giro_id'])->update([
            'is_used' => 1,
            'reff_no' => $trx_code
          ]);
        }
      }

      foreach ($request->paymentbp_detail as $key => $value) {
        if(is_null($value))
            continue;

        DebtPayment::create([
          'header_id' => $id,
          'journal_id' => $j->id,
          'create_by' => auth()->id(),
          'reff' => $value['nmrbp'],
          'total' => $value['totalbp'],
          'description' => $value['description']??null,
          'cash_account_id' => $value['bp_cash_account_id']??null,
          'cek_giro_id' => $value['cek_giro_id']??null,
        ]);
      }

      DB::commit();
    }

    public function draftListHutangDetail($id)
    {
        $item = Payable::with('payableDetails','company','contact','journal','type_transaction')
            ->find($id);
        return Response::json(['item'=>$item]);
    }

}

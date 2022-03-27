<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Bill;
use App\Model\BillDetail;
use App\Model\BillPayment;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\CekGiro;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\UmCustomer;
use App\Model\UmCustomerDetail;
use App\Model\JobOrder;
use App\Model\Invoice;
use App\Utils\TransactionCode;
use App\Abstracts\BillFile;
use DB;
use Response;
use PDF;
use bPDF;
use Exception;

class BillReceivableController extends Controller
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
        'customer_id' => 'required',
        'date_request' => 'required',
        'date_receive' => 'required',
        'total' => 'required',
        'overpayment' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'billReceivable');
      $code->setCode();
      $trx_code = $code->getCode();

      $b=Bill::create([
        'company_id' => $request->company_id,
        'customer_id' => $request->customer_id,
        'create_by' => auth()->id(),
        'date_request' => dateDB($request->date_request),
        'date_receive' => dateDB($request->date_receive),
        'total' => $request->total,
        'description' => $request->description,
        'code' => $trx_code,
      ]);
      foreach ($request->detail as $key => $value) {
        if(empty($value)) continue;
        $r=Receivable::find($value['receivable_id']);
        $rd=ReceivableDetail::create([
          'header_id' => $r->id,
          'type_transaction_id' => 28, //bill receivable
          'code' => $trx_code,
          'date_transaction' => dateDB($request->date_request),
          'relation_id' => $b->id,
          'credit' => $value['bill'],
          'is_journal' => 0
        ]);

        BillDetail::create([
          'header_id' => $b->id,
          'type_transaction_id' => $r->type_transaction_id,
          'code' => $r->code,
          'receivable_id' => $value['receivable_id'],
          'receivable_detail_id' => $rd->id,
          'create_by' => auth()->id(),
          'bill' => $value['bill'],
          'leftover' => $value['leftover'],
          'total_bill' => $value['total'],
          'description' => @$value['description'],
        ]);

        if ($value['leftover']<=0) {
          if ($r->type_transaction_id==26) {
            // jika transaksi invoice
            if (isset($r->relation_id)) {
              JobOrder::where('invoice_id', $r->relation_id)->update([
                'status' => 2
              ]);
            }
          }
        }

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
      $data['item']=Bill::with('company','customer')->where('id', $id)->first();
      $data['detail']=BillDetail::with('type_transaction')->where('header_id', $id)->get();
      $data['payment']=BillPayment::with('cash_account','cek_giro')->where('header_id', $id)->whereNull('reff')->get();
      $data['paymentbp']=BillPayment::with('cash_account','cek_giro')->where('header_id', $id)->whereNotNull('reff')->get();
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

    public function payment($id)
    {
      $data['item']=Bill::with('company','customer')->where('id', $id)->first();
      $data['detail']=BillDetail::with('type_transaction')->where('header_id', $id)->get();
      $data['account']=Account::with('parent')->where('is_base',0)->select('id','code','name','no_cash_bank')->get();
      //by andre 31 juli
      $data['accountall']=DB::table('accounts')->where('is_base',0)->select('id','deep','code','name','no_cash_bank')->get();
      // $data['accountall']=Account::with('parent')->select('id','code','name','no_cash_bank')->get();
      //end by andre
      $data['cek_giro']=CekGiro::join('journals', 'journals.id', 'journal_id')->where('journals.status', 3)->where('penerbit_id', $data['item']->customer_id)->where('is_kliring',1)->get();
      $data['receivable']=Receivable::where('contact_id', $data['item']->customer_id)->select('id','code',DB::raw('debet-credit as total'))->whereRaw('(debet-credit) > 0')->get();
      $data['uang_muka']=UmCustomer::where('contact_id', $data['item']->customer_id)->select('id','code',DB::raw('credit-debet as total'))->whereRaw('(credit-debet) > 0')->get();
      $data['payment']=BillPayment::with('cash_account','cek_giro')->where('header_id', $id)->whereNull('reff')->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    // method by andre
    // untuk validasi bukti potong(menambah kredit)

    // public function cekvalid(Request $request, $id)
    // {
    //   $bp=[];
    //   $nilaibp=[];


    //   $cekjurnal=JournalDetail::where('header_id','=',$request->journal_id)
    //   ->where()
    //   ->get();
    //   // foreach ($request->paymentbp as $key => $value) {
    //   //   if (empty($value)) {
    //   //     continue;
    //   //   }
    //   //   if ($value['bp_cash_account_id']??null) {
    //   //     $bp[]=$value['bp_cash_account_id'];
    //   //     $nilaibp[]=$value['totalbp'];
    //   //   }
    //   // }

    //   // foreach ($bp as $key => $value) {
    //   //     JournalDetail::create([
    //   //     'header_id' => $j->id,
    //   //     'account_id' => $value,
    //   //     // 'cash_category_id' => $cid,
    //   //     'debet' => 0,
    //   //     'credit' => $nilaibp[$key],
    //   //   ]);
    //   //   }

    //   JournalDetail::create([
    //       'header_id' => $request->journal_id,
    //       'account_id' => $request->cash_account_id,
    //       // 'cash_category_id' => $cid,
    //       'debet' => 0,
    //       'credit' => $request->total,
    //     ]);
    // }
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
      if (empty($cekDefault->bukti_potong)) {
        return Response::json(['message' => 'Akun Default Bukti Potong Belum Ditentukan!'],500);
      }



      $idutkbill=DB::table('bill_payments')
            ->select('header_id')
            ->where('id', $request->id)
            ->get();

      foreach ($idutkbill as $target) {

               $tabelbill=DB::table('bills')
                ->select('company_id')
                ->where('id', $target->header_id)
                ->get();

                 foreach ($tabelbill as $targetdlm) {
                  $code = new TransactionCode($targetdlm->company_id, 'billReceivablePayment');
                  $code->setCode();
                  $trx_code = $code->getCode();
                  $j=Journal::create([
                    'company_id' => $targetdlm->company_id,
                    'type_transaction_id' => 30,
                    'date_transaction' => $todaydate,
                    'created_by' => auth()->id(),
                    'code' => $trx_code,
                    'description' => 'Pembayaran Tagihan Piutang - ',
                    'debet' => 0,
                    'credit' => 0,
                  ]);

                  JournalDetail::create([
                    'header_id' => $j->id,
                    'account_id' => $cekDefault->bukti_potong,
                    // 'cash_category_id' => $cid,
                    'debet' => $request->total,
                    'credit' => 0,
                  ]);

                  JournalDetail::create([
                  'header_id' => $j->id,
                  'account_id' => $request->cash_account_id,
                  // 'cash_category_id' => $cid,
                  'debet' => 0,
                  'credit' => $request->total,
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



      DB::table('bill_payments')
            ->where('id', $request->id)
            ->update(['valid' => 1]);


       foreach ($idutkbill as $target) {
               DB::table('bills')
                ->where('id', $target->header_id)
                ->update(['status' => 3]);
            };

    }


    // method by andre
    // upload file BP
    public function uploadBP(Request $request,$id){
    $request->validate([
        'file' => 'required|mimetypes:image/jpeg,image/png,application/pdf',
      ],[
        'file.mimetypes' => 'File Harus Berupa Gambar atau PDF!',
        'file.required' => 'File belum ada!'
      ]);


    $file=$request->file('file');
    $file_name="BP_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();

    DB::table('bill_payments')
            ->where('id', $id)
            ->update(['filename' => $file_name]);

    $file->move(public_path('files'),$file_name);
  }

    public function store_payment(Request $request, $id)
    {
        // return Response::json($request,500,[],JSON_NUMERIC_CHECK);
        // dd($request->all());
        DB::beginTransaction();
        $bill=Bill::find($id);

        $payments = DB::table('bill_payments')->whereHeaderId($id)->get();
        foreach($payments as $p) {
            $journal = DB::table('journals')->whereId($p->journal_id)->first();
            if($journal) {
                if($journal->status == 3) {
                    throw new Exception('Journal was approved');
                }
            }
            DB::table('journal_details')->whereHeaderId($p->journal_id)->delete();
            DB::table('journals')->whereId($p->journal_id)->delete();
            DB::table('bill_payments')->whereId($p->id)->delete();
        }

        $code = new TransactionCode($bill->company_id, 'billReceivablePayment');
        $contact = Contact::find($bill->customer_id);
        $code->setCode();
        $trx_code = $code->getCode();
        $totalPaymentBp=$request->total_paymentbp;
        $cash_account_krg=$request->cash_account_id_krg;
        $statusbill=2;
        $contact=Contact::find($bill->customer_id);
      // if ($totalPaymentBp>0) {
      //   $statusbill=3;
      // }
      Bill::find($id)->update([
        'status' => $statusbill,
        'code_receive' => $trx_code,
        'date_receive' => dateDB($request->date_receive),
      ]);

      //hitung Kas/Bank-Cek/giro
      $giro=[];
      $kas=[];
      $bp=[];
      $um=[];
      $nilaiKas=[];
      $nilaibp=[];
      $giroA=0;
      $kasA=0;
      $bpA=0;

      $totalPayment=$request->total_payment;
      $type_transaction_id = 28;


      $totalBayar=0;
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $val=(object)$value;
        if (isset($val->um_customer_id)) {
          $totalBayar+=abs($val->bill);
        }
      }
      foreach ($request->payment_detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $val=(object)$value;
        $totalBayar+=$val->total;
      }

      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        // dd($request->total_payment);
        //jika ada receivable id
        if ($value['receivable_detail_id']??null) {
          $receivable_id = $value['receivable_id'];
          if ($value['total_bill'] < $totalBayar) {
            $byr=$value['total_bill'];
            $totalBayar-=$value['total_bill'];
          } else {
            $byr=$totalBayar;
            $totalBayar=0;
          }
          ReceivableDetail::find($value['receivable_detail_id'])->update([
            'credit' => $byr
          ]);
        } else {
          if ($value['receivable_id']??null) {
            //simpan di tabel piutang detail
            if ($value['total_bill'] < $totalBayar) {
              $byr=$value['total_bill'];
              $totalBayar-=$value['total_bill'];
            } else {
              $byr=$totalBayar;
              $totalBayar=0;
            }

            $rds=ReceivableDetail::create([
              'header_id' => $value['receivable_id'],
              'type_transaction_id' => $type_transaction_id, //bill receivable
              'code' => $trx_code,
              'date_transaction' => dateDB($request->date_receive),
              'relation_id' => $id,
              'credit' => $byr,
              'is_journal' => 0
            ]);
          }
        }
        //jika ada id detail
        if ($value['id']??false) {
          BillDetail::where('id', $value['id']??0)->update([
            'bill' => $value['bill'],
            'description' => $value['description']??null,
          ]);
        } else {
          BillDetail::create([
            'header_id' => $id,
            'type_transaction_id' => $type_transaction_id,
            'code' => $trx_code,
            'receivable_id' => $value['receivable_id']??null,
            'receivable_detail_id' => $rds->id??null,
            'um_customer_id' => $value['um_customer_id']??null,
            'create_by' => auth()->id(),
            'bill' => $value['bill'],
            'leftover' => $value['leftover'],
            'total_bill' => $value['total_bill'],
            'description' => @$value['description'],
            'jenis' => $value['jenis']??1,
            'account_id' => $value['account_id']??null,
          ]);
        }

        if(isset($value['um_customer_id'])) {
            //untuk pembayaran menggunakan uang muka
            $um_customer = UmCustomer::find($value['um_customer_id']);
            $nilai_um = -1 * $value['bill'];
            UmCustomerDetail::create([
                'header_id' => $um_customer->id,
                'type_transaction_id' => $type_transaction_id,
                'code' => $trx_code,
                'date_transaction' => dateDB($request->date_receive),
                'debet' => $nilai_um,
                'credit' => 0,
                'description' => $value['description']
            ]);
            $um_customer->update(["debet" => $um_customer->debet + $nilai_um]);
            $um []= [
                "account_id" => $contact->akun_um_customer,
                "nilai" => $nilai_um
            ];
        }
        //jika ada cndn
        // if ($value['account_id']??null) {
        //   if (in_array($value['no_cash_bank'],[1,2])) {
        //     $i=CashTransaction::create([
        //       'company_id' => $bill->company_id,
        //       'type_transaction_id' => 30, // pembayaran tagihan piutang
        //       // 'code' => $trx_code,
        //       // 'journal_id' => $j->id,
        //       'reff' => $bill->code,
        //       'jenis' => $value['jenis'],
        //       'type' => $value['no_cash_bank'],
        //       'description' => 'Pembayaran Tagihan Piutang - '.$bill->code,
        //       'total' => $value['bill'],
        //       'account_id' => $value['account_id'],
        //       'date_transaction' => dateDB($request->date_receive),
        //       'status_cost' => 3,
        //       'created_by' => auth()->id()
        //     ]);
        //
        //     CashTransactionDetail::create([
        //       'header_id' => $i->id,
        //       'account_id' => $value['cash_account_id'],
        //       'contact_id' => $bill->customer_id,
        //       'amount' => $value['total'],
        //       'description' => @$value['description'],
        //       'jenis' => 1
        //     ]);
        //
        //   }
        // }
      }

      foreach ($request->payment_detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
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


                ReceivableDetail::create([
                  'header_id' => $receivable_id,
                  'type_transaction_id' => 28, //bill receivable
                  'code' => $trx_code,
                  'date_transaction' => dateDB($request->date_receive),
                  'relation_id' => $value['bp_cash_account_id'],
                  'credit' => $value['totalbp'],
                  'is_journal' => 0
                ]);
        }
      }
      //menjurnal dahulu
      $j=Journal::create([
        'company_id' => $bill->company_id,
        'type_transaction_id' => 30,
        'date_transaction' => dateDB($request->date_receive),
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'description' => 'Pembayaran Tagihan Piutang - '.$bill->code,
        'debet' => 0,
        'credit' => 0,
      ]);

      //jurnal detail kas
      foreach ($kas as $key => $value) {
        $cekCC=cekCashCount($bill->company_id,$value);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value,
          // 'cash_category_id' => $cid,
          'debet' => $nilaiKas[$key],
          'credit' => 0,
        ]);
      }

      //jurnal penjualan - kredit
      $cekDefault=AccountDefault::first();
      if (empty($cekDefault->penjualan)) {
        return Response::json(['message' => 'Akun Default Penjualan Belum Ditentukan!'],500);
      }
      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $cekDefault->penjualan,
        // 'cash_category_id' => $cid,
        'debet' => 0,
        'credit' => $request->total_tagih + $request->total_um,
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
          'debet' => $nilaibp[$key],
          'credit' => 0,
        ]);
        }

      }

      //jika ada lebih bayar(versi awal,comment by andre)
      // if ($request->leftover_payment>0) {
      //   if (empty($cekDefault->lebih_bayar_piutang)) {
      //     return Response::json(['message' => 'Akun Default Lebih Bayar Piutang Belum Ditentukan!'],500);
      //   }
      //   JournalDetail::create([
      //     'header_id' => $j->id,
      //     'account_id' => $cekDefault->lebih_bayar_piutang,
      //     'debet' => $request->leftover_payment,
      //     'credit' => 0,
      //   ]);
      // }


      //jika ada lebih bayar(versi baru,made by andre)
      $testkrg=$request->total_tagih-$request->total_payment;
      if ($testkrg>0) {
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


      if ($testkrg<0) {
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

      if(!empty($um)) {
        foreach($um as $uang_muka) {
            JournalDetail::create([
                'header_id' => $j->id,
                'account_id' => $uang_muka['account_id'],
                'debet' => $uang_muka['nilai'],
                'credit' => 0,
            ]);
        }
      }

      //backup andre
      // if ($request->leftover_payment>0) {
      //   if (empty($cekDefault->lebih_bayar_piutang)) {
      //     return Response::json(['message' => 'Akun Default Lebih Bayar Piutang Belum Ditentukan!'],500);
      //   }
      //   JournalDetail::create([
      //     'header_id' => $j->id,
      //     'account_id' => $cash_account_krg,
      //     'debet' => $request->leftover_payment,
      //     'credit' => 0,
      //   ]);
      // }


      // if ($request->leftover_payment<0) {
      //   if (empty($cekDefault->lebih_bayar_piutang)) {
      //     return Response::json(['message' => 'Akun Default Lebih Bayar Piutang Belum Ditentukan!'],500);
      //   }
      //   JournalDetail::create([
      //     'header_id' => $j->id,
      //     'account_id' => $cash_account_krg,
      //     'debet' => 0,
      //     'credit' => $request->leftover_payment,
      //   ]);
      // }
      //end backup andre

      //simpan bill payment
      foreach ($request->payment_detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        BillPayment::create([
          'header_id' => $id,
          'journal_id' => $j->id,
          'create_by' => auth()->id(),
          'payment_type' => $value['payment_type'],
          'total' => floatval($value['total']),
          'description' => $value['description']??null,
          'cash_account_id' => $value['cash_account_id']??null,
          'cek_giro_id' => $value['cek_giro_id']??null,
        ]);

        if ($value['cash_account_id']??null) {
          //jika pakai kas simpan di transaksi kas
          $acc=Account::find($value['cash_account_id']);
          $i=CashTransaction::create([
            'company_id' => $bill->company_id,
            'type_transaction_id' => 30, // pembayaran tagihan piutang
            // 'code' => $trx_code,
            'journal_id' => $j->id,
            'reff' => $bill->code,
            'jenis' => 1,
            'type' => $acc->no_cash_bank,
            'description' => 'Pembayaran Tagihan Piutang - '.$bill->code,
            'total' => $value['total'],
            'account_id' => $acc->id,
            'date_transaction' => dateDB($request->date_receive),
            'status_cost' => 3,
            'created_by' => auth()->id()
          ]);

          $acd=AccountDefault::first();
          if (empty($acd->penjualan)) {
            return Response::json(['message' => 'Akun Default Penjualan Belum Ditentukan!'],500);
          }

          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $acd->penjualan,
            'contact_id' => $bill->customer_id,
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
          if(!is_null($value)) {
            BillPayment::create([
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
      }

      DB::commit();
    }

    public function store_pi_data(Request $request)
    {
        $request->validate([
            'bill_id' => 'required',
            'due_date' => 'required',
            'details' => 'required'
        ]);

        $bill = Bill::with('company','customer')
            ->where('id', $request->bill_id)
            ->first();

        DB::beginTransaction();

        foreach($request->details as $detail) {
            $billDetail = BillDetail::find($detail['detail_id']);
            $billDetail->update([
                "pi_lampiran" => $detail['lampiran'],
                "pi_kapal" => $detail['kapal']
            ]);
        }

        $bill->update(['pi_due_date' => $request->due_date]);

        DB::commit();
    }

    public function print_pengantar_invoice($id)
    {
        $bill = Bill::with('company','customer')
            ->where('id', $id)
            ->first();
        $isCopy = $bill->pi_print_count > 0 ? "COPY" : "ASLI";
        $printCountAwal = $bill->pi_print_count;
        $printCount = $printCountAwal + 1;
        $bill->update(['pi_print_count' => $printCount]);
        $billDetails = BillDetail::where('header_id', $id)->get();

        $data = [
            "bill" => [
                "due_date" => $bill->pi_due_date,
                "customer_name" => $bill->customer->name,
                "customer_address" => $bill->customer->address,
                "total" => $bill->total,
                "isCopy" => $isCopy,
                // "isCopy" => (($printCountAwal > 0) ? "COPY" : "ASLI")
            ], "details" => [] ];

        foreach($billDetails as $detail) {
            $data["details"] []= [
                "code" => $detail->code,
                "lampiran" => $detail->pi_lampiran,
                "kapal" => $detail->pi_kapal,
                "description" => $detail->description,
                "total" => $detail->bill
            ];
        }
        $data['remark']=DB::table('print_remarks')->first();
        return bPDF::loadView('pdf.invoice.pengantar-invoice', $data)
                   ->setPaper("A5")
                   ->stream();
    }

    /*
      Date : 29-08-2020
      Description : Menyimpan file bukti pembayaran
      Developer : Didin
      Status : Create
    */
    public function storeFile(Request $request, $id) {
        $request->validate([
            'file' => 'required'
        ]);
        $status_code = 200;
        $msg = 'Data successfully saved';
        try {
            BillFile::store($request->file('file'), $id);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $status_code = 421;
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar file
      Developer : Didin
      Status : Create
    */
    public static function indexFile($id) {
        $dt = BillFile::index($id);
        $data['message'] = 'OK';
        $data['data'] = $dt;
        return response()->json($data);
    }

    /*
      Date : 29-08-2020
      Description : Menyimpan file bukti pembayaran
      Developer : Didin
      Status : Create
    */
    public function destroyFile($id, $bill_file_id) {
        $status_code = 200;
        $msg = 'Data successfully removed';
        try {
            $exist = DB::table('bills')
            ->whereId($id)
            ->count('id');
            if($exist == 0) {
                throw new Exception('Data not found');
            }
            BillFile::destroy($bill_file_id);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $status_code = 421;
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}

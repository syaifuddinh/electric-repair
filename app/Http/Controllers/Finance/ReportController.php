<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Account;
use App\Model\Company;
use App\Model\TypeTransaction;
use App\Model\Contact;
use App\Model\AccountDefault;
use Excel;
use DB;
use PDF;
use Response;
use Carbon\Carbon;
use PHPExcel_Style_Fill;

class ReportController extends Controller
{
  public function account(Request $request)
  {
    $data['data']=Account::orderBy('code')->get();
    // dd($data);
    // Excel::create('Daftar Akun', function($excel) use ($data) {
    //   $excel->sheet('Daftar Akun', function($sheet) use ($data){
    //     $sheet->loadView('export.account', $data);
    //     // $sheet->setStyle([
    //     //   'font' => [
    //     //     'name' => 'Calibri',
    //     //     'size' => 11,
    //     //   ],
    //     // ]);
    //   });
    // })->export('xls');
    return PDF::loadView('export.account', $data)->stream('Daftar Akun.pdf');
  }

  public function journal()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['type_transaction']=TypeTransaction::all();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ledger()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code')->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ledger_receivable()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['customer']=Contact::where('is_pelanggan', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ledger_payable()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['supplier']=Contact::whereRaw("is_vendor = 1 or is_supplier=1")->where('vendor_status_approve', 2)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ledger_um_supplier()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['supplier']=Contact::whereRaw("is_vendor = 1 or is_supplier=1")->where('vendor_status_approve', 2)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ledger_um_customer()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['customer']=Contact::where('is_pelanggan', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function neraca_saldo()
  {
    $data['company']=companyAdmin(auth()->id());
    // $data['customer']=Contact::where('is_pelanggan', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function arus_kas()
  {
    $data['company']=companyAdmin(auth()->id());
    // $data['customer']=Contact::where('is_pelanggan', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function laba_rugi()
  {
    $data['company']=companyAdmin(auth()->id());
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function ekuitas()
  {
    $data['company']=companyAdmin(auth()->id());
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function posisi_keuangan()
  {
    $data['company']=companyAdmin(auth()->id());
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function export_journal(Request $request)
  {
    // dd($request);
    $wr="1=1";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND company_id = $request->company_id";
    }
    if (isset($request->status)) {
      $wr.=" AND status = $request->status";
    }
    if (isset($request->type_transaction_id)) {
      $wr.=" AND type_transaction_id = $request->type_transaction_id";
    }

    $journal = Journal::whereRaw($wr);

    // Apakah menampilkan yang nilainya 0 atau tidak
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
        $journal = $journal->where([
          ['debet', '!=', 0],
          ['credit', '!=', 0]
        ]);
    }
    $journal = $journal->orderBy('date_transaction')->get();

    $data['data']= $journal;
    $data['company']=Company::find($request->company_id);
    $data['start']=$request->start_date;
    $data['end']=$request->end_date;
    return PDF::loadView('export.journal', $data)->stream('Jurnal Umum.pdf');
  }

  /*
      Date : 18-04-2020
      Description : Export buku besar
      Developer : Didin
      Status : Edit
  */
  public function export_ledger(Request $request)
  {
    $response = null;
    try {
      $dt_end = (isset($request->end_date)) ?
        date('Y-m-d', strtotime($request->end_date)) :
        Carbon::now()->format('Y-m-d');
      $wr="1=1";
      $wr2="";
      if (isset($request->start_date) && isset($request->end_date)) {
        $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
        $dt_start=date('Y-m-d', strtotime($request->start_date));
      } else {
        $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
        $wr.=" AND journals.date_transaction >= '$dt_start'";
      }

      $is_audit = $request->is_audit ?? 0;
      $wr.=" AND journals.is_audit = $is_audit";
      $wr2.=" AND journals.is_audit = $is_audit";

      if (isset($request->company_id)) {
        $wr.=" AND journals.company_id = $request->company_id";
        $wr2.=" AND journals.company_id = $request->company_id";
      }
      $wr3="1=1";
      if (isset($request->account_id)) {
        $wr3.=" AND id = ".$request->account_id;
      }
      $data=[];
      // $account=Account::where('is_base', 0)->whereRaw($wr3)->orderBy('code')->get();
      $account=DB::table('accounts')->whereRaw($wr3)->where('is_base', 0)->select('code','name','id','jenis','description')->orderBy('code')->get();
      // dd($account);
      foreach ($account as $value) {
        // $saldo=JournalDetail::leftJoin('journals','journals.id','=','journal_details.header_id')
        //                       ->where('journals.date_transaction', '<', $dt_start)
        $sqlsaldo1="select ifnull(sum(if(accounts.jenis=1,journal_details.debet-journal_details.credit,journal_details.credit-journal_details.debet)),0) as saldo from journal_details
              left join journals on journals.id = journal_details.header_id
              left join type_transactions on type_transactions.id = journals.type_transaction_id
              left join accounts on accounts.id = journal_details.account_id
              where journal_details.account_id = $value->id and journals.status = 3 and type_transactions.slug like '%saldo%' and $wr";
        $saldo=DB::select($sqlsaldo1)[0];
        // dd($saldo);
        if ($saldo->saldo==0) {
          $sqlsaldo2="select ifnull(sum(if(accounts.jenis=1,journal_details.debet-journal_details.credit,journal_details.credit-journal_details.debet)),0) as saldo from journal_details
                left join journals on journals.id = journal_details.header_id
                left join type_transactions on type_transactions.id = journals.type_transaction_id
                left join accounts on accounts.id = journal_details.account_id
                where journal_details.account_id = $value->id and journals.status = 3 $wr2 and journals.date_transaction < '$dt_start'";
          $saldo=DB::select($sqlsaldo2)[0];
        }
        $pos=$saldo->saldo;
        // $get=JournalDetail::leftJoin('journals','journals.id','=','journal_details.header_id')->whereRaw($wr." AND journals.status = 3")->where('journals.status', 3)->orderBy('journals.date_transaction')->select('journals.date_transaction','journals.code','journal_details.description','journal_details.debet','journal_details.credit')->get();
        $get=DB::select("select journals.date_transaction,journals.code,journal_details.description,journal_details.debet, journal_details.credit from journal_details left join journals on journal_details.header_id = journals.id left join type_transactions on type_transactions.id = journals.type_transaction_id where $wr and journals.status = 3 and journal_details.account_id = $value->id and type_transactions.slug not like '%saldo%'");
        // dd($get);
        $sqlSaldoAkhir="select ifnull(sum(if(accounts.jenis=1,journal_details.debet-journal_details.credit,journal_details.credit-journal_details.debet)),0) as saldo from journal_details
                left join journals on journals.id = journal_details.header_id
                left join type_transactions on type_transactions.id = journals.type_transaction_id
                left join accounts on accounts.id = journal_details.account_id
                where journal_details.account_id = $value->id and journals.status = 3 $wr2 and journals.date_transaction <= '$dt_end'";
        $saldo_akhir=DB::select($sqlSaldoAkhir)[0]->saldo;

        $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');

        if(($prevent_zero && ($saldo_akhir != 0 || count($get) > 0)) || !$prevent_zero) {
            $data[]=[
                'account' => [
                    'name' => $value->code.' - '.$value->name,
                    'jenis' => $value->jenis,
                    'description' => $value->description,
                ],
                'saldo' => $pos,
                'detail' => $get
            ];
        }

      }

      $data['data']=$data;
      $data['company']=Company::find($request->company_id);
      $data['start']=$request->start_date;
      $data['end']=$request->end_date;
      $response = PDF::loadView('export.ledger', $data)->stream();
    } catch (Exception $e) {
      $response = Response::json(['message' => $e->getMessage()]);
    }
    return $response;
  }

  public function export_ledger_receivable(Request $request)
  {
    $start_date = (isset($request->start_date)) ?
        date('Y-m-d', strtotime($request->start_date)) :
        Carbon::parse('first day of this month')->format('Y-m-d');
    $end_date = (isset($request->end_date)) ?
        date('Y-m-d', strtotime($request->end_date)) :
        Carbon::now()->format('Y-m-d');

    $whereCustomer = (isset($request->customer_id)) ? " AND id = {$request->customer_id} " : "";
    $whereSaldoAwal = "AND rd.date_transaction < '{$start_date}' ";
    $whereSaldoAkhir = "AND rd.date_transaction <= '{$end_date}' ";

    $data=['data'=>[]];
    $customer = DB::table('contacts')
        ->whereRaw("is_pelanggan = 1 {$whereCustomer}")
        ->select('id','name')->get();
    $details_query = "FROM receivable_details rd "
        . "LEFT JOIN receivables r ON rd.header_id = r.id "
        . "LEFT JOIN type_transactions t ON rd.type_transaction_id = t.id "
        . "WHERE 1 = 1 ";

    if(isset($request->company_id))
        $details_query .= "AND r.company_id = {$request->company_id} ";

    $saldo_query = "SELECT IFNULL(SUM(rd.debet-rd.credit),0) as saldo $details_query ";
    $saldo_debet_query = "SELECT IFNULL(SUM(rd.debet),0) as saldo  ";
    $saldo_credit_query = "SELECT IFNULL(SUM(rd.credit),0) as saldo  ";

    foreach ($customer as $value) {
        $saldo_sql = $saldo_query . "AND r.contact_id = {$value->id} ";
        $saldo_debet_sql = $saldo_debet_query . $details_query . "AND r.contact_id = {$value->id} ";
        $saldo_credit_sql = $saldo_credit_query . $details_query . "AND r.contact_id = {$value->id} ";

        $saldo_awal_sql = DB::select($saldo_sql . $whereSaldoAwal)[0];
        $saldo_akhir_sql = DB::select($saldo_sql . $whereSaldoAkhir)[0];
        $saldo_debet_sql = DB::select($saldo_debet_sql . $whereSaldoAkhir)[0];
        $saldo_credit_sql = DB::select($saldo_credit_sql . $whereSaldoAkhir)[0];
        $details = DB::select("SELECT rd.date_transaction, rd.code, rd.description, rd.debet, rd.credit "
            . $details_query
            . "AND rd.date_transaction BETWEEN '{$start_date}' AND '{$end_date}' "
            . "AND r.contact_id = {$value->id}");
        $saldo_awal = $saldo_awal_sql->saldo;
        $saldo_akhir = $saldo_akhir_sql->saldo;
        $saldo_debet = $saldo_debet_sql->saldo;
        $saldo_credit = $saldo_credit_sql->saldo;

        $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');
        if(($prevent_zero && ($saldo_akhir != 0 || count($details) > 0)) || !$prevent_zero) {
            $data['data'] []= [
                'contact' => [
                    'name' => $value->name,
                ],
                'saldo_awal' => $saldo_awal,
                'saldo_akhir' => $saldo_akhir,
                'saldo_debet' => $saldo_debet,
                'saldo_credit' => $saldo_credit,
                'detail' => $details
            ];
        }
    }

    $data['company']=Company::find($request->company_id);
    $data['start']=$request->start_date;
    $data['end']=$request->end_date;

    if(empty($data['data'])) {
        echo("Data Tidak ditemukan!!");
        return;
    }

    return PDF::loadView('export.ledger_receivable', $data)->stream('Buku Besar Piutang.pdf');
  }

  public function export_ledger_payable(Request $request)
  {
    $start_date = (isset($request->start_date)) ?
        date('Y-m-d', strtotime($request->start_date)) :
        Carbon::parse('first day of this month')->format('Y-m-d');

    $end_date = (isset($request->end_date)) ?
        date('Y-m-d', strtotime($request->end_date)) :
        Carbon::now()->format('Y-m-d');

    $whereCustomer = (isset($request->customer_id)) ? " AND id = {$request->customer_id} " : "";
    $whereSaldoAwal = "AND pd.date_transaction < '{$start_date}' ";
    $whereSaldoAkhir = "AND pd.date_transaction <= '{$end_date}' ";
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" and pd.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND pd.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND p.company_id = $request->company_id";
      $wr2.=" AND p.company_id = $request->company_id";
    }

    $wr3="1=1";
    if (isset($request->supplier_id)) {
      $wr3.=" AND p.contact_id = ".$request->supplier_id;
    }
    $data=["data" => []];
    $customer=DB::table('contacts')
        ->whereRaw("(is_supplier=1 or is_vendor=1) {$whereCustomer}")
        ->where('vendor_status_approve', 2)
        ->select('id','name')->get();
    $details_query = "FROM payable_details pd "
        . "LEFT JOIN payables p ON pd.header_id = p.id "
        . "LEFT JOIN type_transactions t ON pd.type_transaction_id = t.id "
        . "WHERE 1 = 1 ";

    if(isset($request->company_id))

        $details_query .= "AND p.company_id = {$request->company_id}";

    $saldo_query = "SELECT IFNULL(SUM(pd.credit-pd.debet),0) as saldo  ";
    $saldo_debet_query = "SELECT IFNULL(SUM(pd.debet),0) as saldo  ";
    $saldo_credit_query = "SELECT IFNULL(SUM(pd.credit),0) as saldo  ";
    foreach ($customer as $value) {
        $saldo_sql = $saldo_query . $details_query . "AND p.contact_id = {$value->id} ";
        $saldo_debet_sql = $saldo_debet_query . $details_query . "AND p.contact_id = {$value->id} ";
        $saldo_credit_sql = $saldo_credit_query . $details_query . "AND p.contact_id = {$value->id} ";

        $saldo_awal_sql = DB::select($saldo_sql . $whereSaldoAwal)[0];
        $saldo_akhir_sql = DB::select($saldo_sql . $whereSaldoAkhir)[0];
        $saldo_debet_sql = DB::select($saldo_debet_sql . $whereSaldoAkhir)[0];
        $saldo_credit_sql = DB::select($saldo_credit_sql . $whereSaldoAkhir)[0];
        $details = DB::select("SELECT pd.date_transaction, pd.code, pd.description, pd.debet, pd.credit "
            . $details_query
            . "AND pd.date_transaction BETWEEN '{$start_date}' AND '{$end_date}' "
            . "AND p.contact_id = {$value->id}");
        $saldo_awal = $saldo_awal_sql->saldo;
        $saldo_akhir = $saldo_akhir_sql->saldo;
        $saldo_debet = $saldo_debet_sql->saldo;
        $saldo_credit = $saldo_credit_sql->saldo;

        $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');
        if(($prevent_zero && ($saldo_akhir != 0 || count($details) > 0)) || !$prevent_zero) {
            $data['data'] []= [
                'contact' => [
                    'name' => $value->name,
                ],
                'saldo_awal' => $saldo_awal,
                'saldo_akhir' => $saldo_akhir,
                'saldo_debet' => $saldo_debet,
                'saldo_credit' => $saldo_credit,
                'detail' => $details
            ];
        }
    }

    $data['company']=Company::find($request->company_id);
    $data['start']=$request->start_date;
    $data['end']=$request->end_date;

    if(empty($data['data'])) {
        echo("Data Tidak ditemukan!!");
        return;
    }

    return PDF::loadView('export.ledger_payable', $data)->stream('Buku Besar Hutang.pdf');
  }

  public function export_ledger_um_supplier(Request $request)
  {
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND um_supplier_details.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND um_supplier_details.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND um_suppliers.company_id = $request->company_id";
      $wr2.=" AND um_suppliers.company_id = $request->company_id";
    }

    $wr3="1=1";
    if (isset($request->supplier_id)) {
      $wr3.=" AND id = ".$request->supplier_id;
    }
    $data=[];
    $customer=Contact::whereRaw("(is_supplier=1 or is_vendor=1)")->where('vendor_status_approve', 2)->whereRaw($wr3)->select('id','name')->get();
    foreach ($customer as $value) {
      $sqlsaldo1="select ifnull(sum(um_supplier_details.debet-um_supplier_details.credit),0) as saldo
      from um_supplier_details
      left join um_suppliers on um_suppliers.id = um_supplier_details.header_id
      left join type_transactions on type_transactions.id = um_supplier_details.type_transaction_id
      where type_transactions.slug like '%saldo%' and um_suppliers.contact_id = $value->id and $wr";
      $saldo=DB::select($sqlsaldo1)[0]->saldo;
      if ($saldo<=0) {
        $sqlsaldo1="select ifnull(sum(um_supplier_details.debet-um_supplier_details.credit),0) as saldo
        from um_supplier_details
        left join um_suppliers on um_suppliers.id = um_supplier_details.header_id
        left join type_transactions on type_transactions.id = um_supplier_details.type_transaction_id
        where um_suppliers.contact_id = $value->id and um_supplier_details.date_transaction < '$dt_start' $wr2";
        $saldo=DB::select($sqlsaldo1)[0]->saldo;
      }

      $sql="SELECT um_supplier_details.date_transaction, um_supplier_details.code, um_supplier_details.description, um_supplier_details.debet, um_supplier_details.credit FROM um_supplier_details LEFT JOIN um_suppliers ON um_suppliers.id = um_supplier_details.header_id left join type_transactions on type_transactions.id = um_supplier_details.type_transaction_id WHERE $wr and type_transactions.slug not like '%saldo%' AND um_suppliers.contact_id = $value->id";
      $dt=DB::select($sql);

      $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');

      if(($prevent_zero && $saldo != 0) || !$prevent_zero) {
        $data[]=[
            'contact' => [
            'name' => $value->name,
            ],
            'saldo' => $saldo,
            'detail' => $dt
        ];
      }
    }
    // dd($data);
    $data['data']=$data;
    $data['company']=Company::find($request->company_id);
    $data['start']=$request->start_date;
    $data['end']=$request->end_date;

    if(empty($data['data'])) {
        echo("Data Tidak ditemukan!!");
        return;
    }

    return PDF::loadView('export.ledger_um_supplier', $data)->stream();
  }

  public function export_ledger_um_customer(Request $request)
  {
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND um_customer_details.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND um_customer_details.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND um_customers.company_id = $request->company_id";
      $wr2.=" AND um_customers.company_id = $request->company_id";
    }

    $dt_end = isset($request->end_date) ?
        date('Y-m-d', strtotime($request->end_date)) :
        date('Y-m-d');

    $wr3="1=1";
    if (isset($request->customer_id)) {
      $wr3.=" AND id = ".$request->customer_id;
    }
    $data=[];
    $customer=Contact::whereRaw("is_pelanggan=1")->whereRaw($wr3)->select('id','name')->get();
    foreach ($customer as $value) {
      $sqlsaldo1="select ifnull(sum(um_customer_details.credit-um_customer_details.debet),0) as saldo
      from um_customer_details
      left join um_customers on um_customers.id = um_customer_details.header_id
      left join type_transactions on type_transactions.id = um_customer_details.type_transaction_id
      where type_transactions.slug like '%saldo%' and um_customers.contact_id = $value->id and $wr";
      $saldo=DB::select($sqlsaldo1)[0]->saldo;
      if ($saldo<=0) {
        $sqlsaldo1="select ifnull(sum(um_customer_details.credit-um_customer_details.debet),0) as saldo
        from um_customer_details
        left join um_customers on um_customers.id = um_customer_details.header_id
        left join type_transactions on type_transactions.id = um_customer_details.type_transaction_id
        where um_customers.contact_id = $value->id and um_customer_details.date_transaction < '$dt_start' $wr2";
        $saldo=DB::select($sqlsaldo1)[0]->saldo;
      }

      $sqlsaldo_akhir="select ifnull(sum(um_customer_details.credit-um_customer_details.debet),0) as saldo
        from um_customer_details
        left join um_customers on um_customers.id = um_customer_details.header_id
        left join type_transactions on type_transactions.id = um_customer_details.type_transaction_id
        where um_customers.contact_id = $value->id and um_customer_details.date_transaction < '$dt_end' $wr2";
      $saldo_akhir = DB::select($sqlsaldo_akhir)[0]->saldo;

      $sql="SELECT um_customer_details.date_transaction, um_customer_details.code, um_customer_details.description, um_customer_details.debet, um_customer_details.credit FROM um_customer_details LEFT JOIN um_customers ON um_customers.id = um_customer_details.header_id left join type_transactions on type_transactions.id = um_customer_details.type_transaction_id WHERE $wr and type_transactions.slug not like '%saldo%' AND um_customers.contact_id = $value->id";
      $dt=DB::select($sql);

        $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');

        if(($prevent_zero && $saldo_akhir != 0) || !$prevent_zero) {
            $data[]=[
                'contact' => [
                    'name' => $value->name,
                ],
                'saldo' => $saldo,
                'detail' => $dt
            ];
        }
    }
    $data['data']=$data;
    $data['company']=Company::find($request->company_id);
    $data['start']=$request->start_date;
    $data['end']=$request->end_date;

    if(empty($data['data'])) {
        echo("Data Tidak ditemukan!!");
        return;
    }

    return PDF::loadView('export.ledger_um_customer', $data)->stream();
  }

  /*
      Date : 18-04-2020
      Description : Export neraca saldo
      Developer : Didin
      Status : Edit
  */
  public function export_neraca_saldo(Request $request)
  {
    $acc_default=DB::table('account_defaults')->first();
    $accounts=DB::table('accounts')->selectRaw('id,code,name,deep,is_base,group_report')->orderBy('code','asc')->get();
    $data = array();
    $laba=0;
    $rugi=0;

    foreach ($accounts as $key => $value) {
      $debet=0;
      $credit=0;
      if ($value->id==$acc_default->laba_bulan_berjalan) {
        array_push($data,[
          'id' => $value->id,
          'code' => $value->code,
          'name' => $value->name,
          'deep' => $value->deep,
          'is_base' => $value->is_base,
          'debet' => 0,
          'credit' => 0,
        ]);
        continue;
      }
      $query=DB::table('journal_details')
      ->leftJoin('journals','journals.id','journal_details.header_id')
      ->where('journal_details.account_id', $value->id)
      ->where('journals.status', 3);


      if ($request->start_date&&$request->end_date) {
        $start=Carbon::parse($request->start_date);
        $end=Carbon::parse($request->end_date);
        $query=$query->whereBetween('journals.date_transaction',[$start,$end]);
      }
      $query->whereIsAudit( $request->is_audit ?? 0 );
      if ($request->company_id) {
        $query=$query->where('journals.company_id', $request->company_id);
      }
      $query=$query->selectRaw('journal_details.debet, journal_details.credit')->orderBy('journal_details.id')->chunk(50, function($chunk) use (&$debet,&$credit) {
        foreach ($chunk as $chk) {
          $debet+=$chk->debet;
          $credit+=$chk->credit;
        }
      });

      // Selisih
      if ($value->group_report==2) {
        $laba+=$credit;
        $rugi+=$debet;

        $debet_j=0;
        $credit_j=0;
      } else {
        if ($debet>$credit) {
          $debet_j=$debet-$credit;
          $credit_j=0;
        } else {
          $debet_j=0;
          $credit_j=$credit-$debet;
        }
      }

      /* TAMPILKAN TIDAK 0
      if ($request->prevent_zero) {
        if ($debet_j==$credit_j) {
          continue;
        }
      }
      */

      array_push($data,[
        'id' => $value->id,
        'code' => $value->code,
        'name' => $value->name,
        'deep' => $value->deep,
        'is_base' => $value->is_base,
        'debet' => $debet_j,
        'credit' => $credit_j,
      ]);
    }

    if ($laba>$rugi) {
      $db=0;
      $cr=$laba-$rugi;
    } else {
      $db=$rugi-$laba;
      $cr=0;
    }
    $data=collect($data)->map(function($val,$key) use ($acc_default,$db,$cr,$request){
      if ($acc_default->laba_bulan_berjalan==$val['id']) {
        $val['debet']=$db;
        $val['credit']=$cr;
      }
      if ($request->prevent_zero=='true') {
        if ($val['debet']==$val['credit']&&$val['is_base']!=1) {
          return null;
        }
      }
      return $val;
    });
    $datas = [
      'data' => $data,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'company' => Company::find($request->company_id)
    ];
    return PDF::loadView('export.neraca_saldo_2', $datas)->stream();

  }

  /*
      Date : 18-04-2020
      Description : Export laba rugi
      Developer : Didin
      Status : Edit
  */
  public function export_laba_rugi(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr2.=" AND journals.company_id = $request->company_id";
    }

    $is_audit = $request->is_audit ?? 0;
      $wr.=" AND journals.is_audit = $is_audit";
      $wr2.=" AND journals.is_audit = $is_audit";

    $wr1 = "";
    if (isset($request->is_not_zero)) {
      $wr1 .= " WHERE
        ( det.jml_debet != 0
        OR det.jml_credit != 0 ) ";
    }
    $parent=Account::where('group_report', 2)->orderBy('code')->get();
    $datas=[];
    // dd($parent);
    foreach ($parent as $value) {
      $sql = "
      SELECT
        IFNULL(SUM( journal_details.credit - journal_details.debet ),0) AS amount
      FROM
        journal_details
        LEFT JOIN accounts ON accounts.id = journal_details.account_id
        LEFT JOIN journals ON journals.id = journal_details.header_id
      WHERE
        journal_details.account_id = $value->id AND $wr AND journals.status = 3 AND journals.type_transaction_id != 53
      ";
      $tarik=DB::select($sql)[0];

        $prevent_zero = (isset($request->prevent_zero) && $request->prevent_zero == 'true');

        if(($prevent_zero && $tarik->amount != 0) || !$prevent_zero) {
            $datas []= [
                'id' => $value->id,
                'deep' => $value->deep,
                'code' => $value->code,
                'name' => $value->name,
                'jenis' => $value->jenis,
                'is_base' => $value->is_base,
                'parent' => $value->parent_id,
                'amount' => $tarik->amount
            ];
        }
    }

    $data = [
      'data' => $datas,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'company' => Company::find($request->company_id)
    ];
    // dd($data);
    return PDF::loadView('export.laba_rugi2', $data)->stream();
  }

  public function export_ekuitas(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr2.=" AND journals.company_id = $request->company_id";
    }

    $wr1 = "";
    if (isset($request->is_not_zero)) {
      $wr1 .= " WHERE
        ( det.jml_debet != 0
        OR det.jml_credit != 0 ) ";
    }
    $parent=Account::with('type')->whereHas('type', function($query){
      $query->whereIn('id',[12,13,14]);
    })->orderBy('code')->get();
    $datas=[];
    // dd($parent);
    $default=AccountDefault::first();
    $SQL_laba_tahun_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE
      accounts.group_report = 2
      AND journals.date_transaction < '$dt_start' and journals.status = 3 AND accounts.group_report = 2
    ".(isset($request->company_id)?' and journals.company_id = '.$request->company_id:'');
    $SQL_laba_bulan_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr and journals.status = 3 AND accounts.group_report = 2";

    $lr_tahun_berjalan = DB::select($SQL_laba_tahun_berjalan)[0]->amount;
    $lr_bulan_berjalan = DB::select($SQL_laba_bulan_berjalan)[0]->amount;

    foreach ($parent as $value) {
      $sql = "
      SELECT
        IFNULL(SUM( journal_details.credit ) - SUM( journal_details.debet ),0) AS amount
      FROM
        journal_details
        LEFT JOIN accounts ON accounts.id = journal_details.account_id
        LEFT JOIN journals ON journals.id = journal_details.header_id
      WHERE
        journal_details.account_id = $value->id AND $wr AND journals.status = 3
      ";
      $tarik=DB::select($sql)[0];
      // $datas[]=[
      //   'id' => $value->id,
      //   'deep' => $value->deep,
      //   'code' => $value->code,
      //   'name' => $value->name,
      //   'is_base' => $value->is_base,
      //   'parent' => $value->parent_id,
      //   'amount' => $tarik->amount
      // ];

      if ($value->id==$default->laba_tahun_berjalan) {
        // dd($lr_tahun_berjalan);
        $datas[]=[
          'id' => $value->id,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'is_base' => $value->is_base,
          'parent' => $value->parent_id,
          'amount' => $lr_tahun_berjalan
        ];
      } elseif ($value->id==$default->laba_bulan_berjalan) {
        // dd($lr_bulan_berjalan);
        $datas[]=[
          'id' => $value->id,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'is_base' => $value->is_base,
          'parent' => $value->parent_id,
          'amount' => $lr_bulan_berjalan
        ];
      } else {
        $datas[]=[
          'id' => $value->id,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'is_base' => $value->is_base,
          'parent' => $value->parent_id,
          'amount' => $tarik->amount
        ];
      }
    }
    // dd($datas);
    // dd($data);
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $datas;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit['amount'] == 0 ) {
            unset( $datas[$key] );
          }
      }
    }
    $data = [
      'data' => $datas,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'company' => Company::find($request->company_id)
    ];
    // dd($data);
    return PDF::loadView('export.ekuitas', $data)->stream();
  }

  public function export_neraca_saldo_banding(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="1=1";
    if (isset($request->start_date1) && isset($request->end_date1)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date1))."' AND '".date('Y-m-d', strtotime($request->end_date1))."'";
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->start_date2) && isset($request->end_date2)) {
      $wr2.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date2))."' AND '".date('Y-m-d', strtotime($request->end_date2))."'";
    } else {
      $dt_start2=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr2.=" AND journals.date_transaction >= '$dt_start2'";
    }

    if (isset($request->company_id1)) {
      $wr.=" AND journals.company_id = $request->company_id1";
    }
    if (isset($request->company_id2)) {
      $wr2.=" AND journals.company_id = $request->company_id2";
    }

    $sql = "
    SELECT
      accounts.deep,
      accounts.is_base,
      accounts.code,
      accounts.name,
      accounts.jenis,
      det.jml_debet AS tot_db,
      det.jml_credit AS tot_cr,
      det2.jml_debet AS tot_db2,
      det2.jml_credit AS tot_cr2
    FROM
      accounts
      LEFT JOIN (
    SELECT
      journal_details.account_id AS acc_id,
    IF
      ( ( accounts.jenis = 1 ), SUM(journal_details.debet) - SUM(journal_details.credit), null ) AS jml_debet,
    IF
      ( ( accounts.jenis = 2 ), SUM(journal_details.credit) - SUM(journal_details.debet), null ) AS jml_credit
    FROM
      journal_details
      INNER JOIN accounts ON journal_details.account_id = accounts.id
      INNER JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr AND journals.status = 3
    GROUP BY
      journal_details.account_id
      ) det ON accounts.id = det.acc_id
      LEFT JOIN (
    SELECT
      journal_details.account_id AS acc_id,
    IF
      ( ( accounts.jenis = 1 ), SUM(journal_details.debet) - SUM(journal_details.credit), null ) AS jml_debet,
    IF
      ( ( accounts.jenis = 2 ), SUM(journal_details.credit) - SUM(journal_details.debet), null ) AS jml_credit
    FROM
      journal_details
      INNER JOIN accounts ON journal_details.account_id = accounts.id
      INNER JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr2 AND journals.status = 3
    GROUP BY
      journal_details.account_id
      ) det2 ON accounts.id = det2.acc_id
    ORDER BY
      accounts.code ASC;
    ";
    // dd($sql);
    $datas = DB::select($sql);

    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $datas;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit->tot_db == 0 &&  $unit->tot_db2 == 0 &&  $unit->tot_cr == 0 &&  $unit->tot_cr2 == 0) {
            unset( $datas[$key] );
          }
      }
    }
    $data = [
      'data' => $datas,
      'start1' => $request->start_date1,
      'end1' => $request->end_date1,
      'start2' => $request->start_date2,
      'end2' => $request->end_date2,
      'company1' => Company::find($request->company_id1),
      'company2' => Company::find($request->company_id2),
    ];
    // dd($data);
    return PDF::loadView('export.neraca_saldo_banding', $data)->stream();
  }

  public function export_ekuitas_banding(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="1=1";
    if (isset($request->start_date1) && isset($request->end_date1)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date1))."' AND '".date('Y-m-d', strtotime($request->end_date1))."'";
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id1)) {
      $wr.=" AND journals.company_id = $request->company_id1";
    }

    if (isset($request->start_date2) && isset($request->end_date2)) {
      $wr2.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date2))."' AND '".date('Y-m-d', strtotime($request->end_date2))."'";
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr2.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id2)) {
      $wr2.=" AND journals.company_id = $request->company_id2";
    }

    $parent=Account::with('type')->whereHas('type', function($query){
      $query->whereIn('id',[12,13,14]);
    })->orderBy('code')->get();
    $datas=[];
    // dd($parent);
    foreach ($parent as $value) {
      $sql = "
      SELECT
        IFNULL(SUM( journal_details.credit ) - SUM( journal_details.debet ),0) AS amount
      FROM
        journal_details
        LEFT JOIN accounts ON accounts.id = journal_details.account_id
        LEFT JOIN journals ON journals.id = journal_details.header_id
      WHERE
        journal_details.account_id = $value->id AND $wr AND journals.status = 3
      ";
      $tarik=DB::select($sql)[0];
      $sql2 = "
      SELECT
        IFNULL(SUM( journal_details.credit ) - SUM( journal_details.debet ),0) AS amount
      FROM
        journal_details
        LEFT JOIN accounts ON accounts.id = journal_details.account_id
        LEFT JOIN journals ON journals.id = journal_details.header_id
      WHERE
        journal_details.account_id = $value->id AND $wr2 AND journals.status = 3
      ";
      $tarik2=DB::select($sql)[0];
      $datas[]=[
        'id' => $value->id,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'is_base' => $value->is_base,
        'parent' => $value->parent_id,
        'amount' => $tarik->amount,
        'amount2' => $tarik2->amount,
      ];
    }
    // dd($datas);
    // dd($data);
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $datas;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit['amount'] == 0 && $unit['amount2'] == 0 ) {
            unset( $datas[$key] );
          }
      }
    }
    $data = [
      'data' => $datas,
      'start' => $request->start_date1,
      'end' => $request->end_date1,
      'start2' => $request->start_date2,
      'end2' => $request->end_date2,
      'company' => Company::find($request->company_id),
      'company2' => Company::find($request->company_id2)
    ];
    // dd($data);
    return PDF::loadView('export.ekuitas_banding', $data)->stream();
  }

  public function export_posisi_keuangan(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=Carbon::parse($request->start_date)->format('Y-m-d');
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr2.=" AND journals.company_id = $request->company_id";
    }
    $default=AccountDefault::first();
    $SQL_laba_tahun_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE
      accounts.group_report = 2
      AND journals.date_transaction < '$dt_start' and journals.status = 3 AND accounts.group_report = 2
    ".(isset($request->company_id)?' and journals.company_id = '.$request->company_id:'');
    $SQL_laba_bulan_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr and journals.status = 3 AND accounts.group_report = 2";

    $lr_tahun_berjalan = DB::select($SQL_laba_tahun_berjalan)[0]->amount;
    $lr_bulan_berjalan = DB::select($SQL_laba_bulan_berjalan)[0]->amount;
    $datas=[];
    $acc_aktiva=Account::where('group_report', 1)->whereBetween('type_id', [1,8])->orderBy('code')->get();
    foreach ($acc_aktiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE $wr AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      $datas['aktiva'][]=[
        'is_base' => $value->is_base,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
      ];
    }
    $acc_pasiva=Account::where('group_report', 1)->whereBetween('type_id', [9,17])->orderBy('code')->get();
    foreach ($acc_pasiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE $wr AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      // dd($value->id);
      if ($value->id==$default->laba_tahun_berjalan) {
        // dd($lr_tahun_berjalan);
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_tahun_berjalan
        ];
      } elseif ($value->id==$default->laba_bulan_berjalan) {
        // dd($lr_bulan_berjalan);
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_bulan_berjalan
        ];
      } else {
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
        ];
      }
    }

    // Apakah menampikan data yang bernilai 0 atau tidak
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
      $units = $datas;
      foreach ($units as $door => $accounts) {
        foreach ($accounts as $key => $unit) {
          if( $unit['amount'] == 0 ) {
            unset( $datas[$door][$key] );
          }
        }
      }
    }
    // =======================================================================

    $data = [
      'data' => $datas,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'company' => Company::find($request->company_id)
    ];
    // dd($data);
    // return view('export.posisi_keuangan',$data);
    // return PDF::loadView('export.posisi_keuangan', $data)->stream();
    Excel::create('Laporan Posisi Keuangan - '.Carbon::now(), function($excel) use ($data) {
      $excel->sheet('Data', function($sheet) use ($data){
        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
          'fill' => [
            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFFFFF']
          ]
        ]);
        $sheet->setWidth([
          'B' => 10,
          'C' => 50,
          'D' => 20,
          'E' => 10,
          'F' => 50,
          'G' => 20,
        ]);
        // $sheet->cells('A1:F1', function($cells){
        //   $cells->setFontWeight('bold');
        // });
        $sheet->setColumnFormat([
          'C' => '@',
          'F' => '@',
          'D' => '#,##0.00',
          'G' => '#,##0.00'
        ]);

        $a=2;
        $i=6;
        $o=6;
        $fori=6;
        $foro=6;
        // $a++;
        $sheet->mergeCells("B$a:G$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("SOLOG");
          $cell->setFontSize(16);
          $cell->setFontWeight('bold');
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:G$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("POSISI KEUANGAN");
          $cell->setFontSize(13);
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:G$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue(($data['company']->name??'Semua Cabang'));
          $cell->setFontSize(11);
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:D$a");
        $sheet->mergeCells("E$a:G$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("AKTIVA");
          $cell->setFontSize(11);
          $cell->setAlignment('left');
        });
        $sheet->cell("E$a", function($cell){
          $cell->setValue("PASIVA");
          $cell->setFontSize(11);
          $cell->setAlignment('right');
        });
        foreach ($data['data']['aktiva'] as $k => $value) {
          $sheet->setCellValue("B$i",$value['code']);

          $sheet->cell("C$i", function($cell) use ($value){
            $cell->setValue(menjorokSpasi($value['deep']).$value['name']);
            if ($value['is_base']==1) {
              $cell->setFontWeight('bold');
            }
          });

          $sheet->setCellValue("D$i",($value['is_base']==0?$value['amount']:''));
          $i++;
        }
        foreach ($data['data']['pasiva'] as $k => $value) {
          $sheet->setCellValue("E$o",$value['code']);

          $sheet->cell("F$o", function($cell) use ($value){
            $cell->setValue(menjorokSpasi($value['deep']).$value['name']);
            if ($value['is_base']==1) {
              $cell->setFontWeight('bold');
            }
          });

          $sheet->setCellValue("G$o",($value['is_base']==0?$value['amount']:''));
          $o++;
        }
        if ($i>$o) {
          $dipakai=$i;
        } else {
          $dipakai=$o;
        }
        $dipakai++;
        $sheet->setCellValue("C$dipakai","TOTAL AKTIVA");
        $sheet->setCellValue("D$dipakai","=SUM(D$fori:D$i)");
        $sheet->setCellValue("F$dipakai","TOTAL PASIVA");
        $sheet->setCellValue("G$dipakai","=SUM(G$foro:G$o)");

      });
    })->export('xls');

  }
  public function export_posisi_keuangan_perbandingan(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="1=1";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=Carbon::parse($request->start_date)->format('Y-m-d');
      $dtstring1=$request->start_date." s/d ".$request->end_date;
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
      $dtstring1=$dt_start." s/d ".date('Y-m-d');
    }
    if (isset($request->start_date2) && isset($request->end_date2)) {
      $wr2.=" and journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date2))."' AND '".date('Y-m-d', strtotime($request->end_date2))."'";
      $dt_start2=Carbon::parse($request->start_date2)->format('Y-m-d');
      $dtstring2=$request->start_date2." s/d ".$request->end_date2;
    } else {
      $dt_start2=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr2.=" AND journals.date_transaction >= '$dt_start2'";
      $dtstring2=$dt_start2." s/d ".date('Y-m-d');
    }
    $wrc1="";
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $cy=DB::table('companies')->where('id', $request->company_id)->select('name')->first();
      $companyname1=$cy->name;
      $wrc1=" and journals.company_id = $request->company_id";
    } else {
      $companyname1="Semua Cabang";
    }
    $wrc2="";
    if (isset($request->company_id2)) {
      $wr2.=" AND journals.company_id = $request->company_id2";
      $cy=DB::table('companies')->where('id', $request->company_id2)->select('name')->first();
      $companyname2=$cy->name;
      $wrc2=" and journals.company_id = $request->company_id";
    } else {
      $companyname2="Semua Cabang";
    }
    $default=AccountDefault::first();
    $SQL_laba_tahun_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE
      accounts.group_report = 2
      AND journals.date_transaction < '$dt_start' and journals.status = 3 AND accounts.group_report = 2
    ".(isset($request->company_id)?' and journals.company_id = '.$request->company_id:'');
    $SQL_laba_bulan_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr and journals.status = 3 AND accounts.group_report = 2";

    $lr_tahun_berjalan = DB::select($SQL_laba_tahun_berjalan)[0]->amount;
    $lr_bulan_berjalan = DB::select($SQL_laba_bulan_berjalan)[0]->amount;
    $data1=[];
    $acc_aktiva=Account::where('group_report', 1)->whereBetween('type_id', [1,8])->orderBy('code')->get();
    foreach ($acc_aktiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE IF($value->group_report=2,$wr,journals.date_transaction < '$dt_start' $wrc1) AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      $data1['aktiva'][]=[
        'is_base' => $value->is_base,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
      ];
    }
    $acc_pasiva=Account::where('group_report', 1)->whereBetween('type_id', [9,17])->orderBy('code')->get();
    foreach ($acc_pasiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE IF($value->group_report=2,$wr,journals.date_transaction < '$dt_start' $wrc1) AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      // dd($value->id);
      if ($value->id==$default->laba_tahun_berjalan) {
        // dd($lr_tahun_berjalan);
        $data1['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_tahun_berjalan
        ];
      } elseif ($value->id==$default->laba_bulan_berjalan) {
        // dd($lr_bulan_berjalan);
        $data1['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_bulan_berjalan
        ];
      } else {
        $data1['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
        ];
      }
    }
    //banding--------------
    $SQL_laba_tahun_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE
      accounts.group_report = 2
      AND journals.date_transaction < '$dt_start2' and journals.status = 3 AND accounts.group_report = 2
    ".(isset($request->company_id2)?' and journals.company_id = '.$request->company_id2:'');
    $SQL_laba_bulan_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr2 and journals.status = 3 AND accounts.group_report = 2";

    $lr_tahun_berjalan = DB::select($SQL_laba_tahun_berjalan)[0]->amount;
    $lr_bulan_berjalan = DB::select($SQL_laba_bulan_berjalan)[0]->amount;
    $data2=[];
    $acc_aktiva=Account::where('group_report', 1)->whereBetween('type_id', [1,8])->orderBy('code')->get();
    foreach ($acc_aktiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE IF($value->group_report=2,$wr2,journals.date_transaction < '$dt_start2' $wrc2) AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      $data2['aktiva'][]=[
        'is_base' => $value->is_base,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
      ];
    }
    $acc_pasiva=Account::where('group_report', 1)->whereBetween('type_id', [9,17])->orderBy('code')->get();
    foreach ($acc_pasiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE IF($value->group_report=2,$wr2,journals.date_transaction < '$dt_start2' $wrc2) AND accounts.id = $value->id and journals.status = 3
      ";
      $main=DB::select($sql)[0];
      // dd($value->id);
      if ($value->id==$default->laba_tahun_berjalan) {
        // dd($lr_tahun_berjalan);
        $data2['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_tahun_berjalan
        ];
      } elseif ($value->id==$default->laba_bulan_berjalan) {
        // dd($lr_bulan_berjalan);
        $data2['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_bulan_berjalan
        ];
      } else {
        $data2['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
        ];
      }
    }

    //end ------------------
    // dd($datas);
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
      $units = $data1['aktiva'];
      $units2 = $data2['aktiva'];
      foreach ($units as $key => $unit) {
          $unit2 = $units[$key];
          if( $unit['amount'] == 0 && $unit2['amount'] == 0 ) {
            unset( $data1['aktiva'][$key] );
            unset( $data2['aktiva'][$key] );
          }
      }

      $units = $data1['pasiva'];
      $units2 = $data2['pasiva'];
      foreach ($units as $key => $unit) {
          $unit2 = $units[$key];
          if( $unit['amount'] == 0 && $unit2['amount'] == 0) {
            unset( $data1['pasiva'][$key] );
            unset( $data2['pasiva'][$key] );
          }
      }
    }
    $data = [
      'data1' => $data1,
      'data2' => $data2,
      'datestring1' => $dtstring1,
      'datestring2' => $dtstring2,
      'company1' => $companyname1,
      'company2' => $companyname2,
    ];
    // dd($data);
    // return view('export.posisi_keuangan',$data);
    // return PDF::loadView('export.posisi_keuangan', $data)->stream();
    Excel::create('Laporan Posisi Keuangan Perbandingan - '.Carbon::now(), function($excel) use ($data) {
      $excel->getDefaultStyle()
      ->getAlignment()
      ->applyFromArray(array(
          'wrap'    => TRUE
      ));
      $excel->sheet('Data', function($sheet) use ($data){
        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
          'fill' => [
            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFFFFF']
          ]
        ]);
        $sheet->setWidth([
          'B' => 10,
          'C' => 50,
          'D' => 20,
          'E' => 20,
          'F' => 10,
          'G' => 50,
          'H' => 20,
          'I' => 20,
        ]);
        // $sheet->cells('A1:F1', function($cells){
        //   $cells->setFontWeight('bold');
        // });
        $sheet->setColumnFormat([
          'C' => '@',
          'G' => '@',
          'D' => '#,##0.00',
          'E' => '#,##0.00',
          'H' => '#,##0.00',
          'I' => '#,##0.00'
        ]);

        $a=2;
        $i=6;
        $o=6;
        $fori=6;
        $foro=6;
        // $a++;
        $sheet->mergeCells("B$a:I$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("SOLOG");
          $cell->setFontSize(16);
          $cell->setFontWeight('bold');
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:I$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("POSISI KEUANGAN");
          $cell->setFontSize(13);
          $cell->setAlignment('center');
        });
        $a++;
        // $sheet->mergeCells("B$a:I$a");
        // $sheet->cell("B$a", function($cell){
        //   $cell->setValue('Semua Cabang');
        //   $cell->setFontSize(11);
        //   $cell->setAlignment('center');
        // });
        $a++;
        $sheet->mergeCells("B$a:C$a");
        $sheet->mergeCells("F$a:G$a");
        $sheet->cell("D$a", function($cell) use ($data){
          $html=$data['company1']."\n".$data['datestring1'];
          $cell->setValue($html);
          $cell->setFontSize(11);
          $cell->setAlignment('right');
        });
        $sheet->cell("H$a", function($cell) use ($data){
          $html=$data['company1']."\n".$data['datestring1'];
          $cell->setValue($html);
          $cell->setFontSize(11);
          $cell->setAlignment('right');
        });

        $sheet->cell("E$a", function($cell) use ($data){
          $html=$data['company2']."\n".$data['datestring2'];
          $cell->setValue($html);
          $cell->setFontSize(11);
          $cell->setAlignment('right');
        });
        $sheet->cell("I$a", function($cell) use ($data){
          $html=$data['company2']."\n".$data['datestring2'];
          $cell->setValue($html);
          $cell->setFontSize(11);
          $cell->setAlignment('right');
        });
        // $sheet->cell("B$a", function($cell){
        //   $cell->setValue("AKTIVA");
        //   $cell->setFontSize(11);
        //   $cell->setAlignment('center');
        //   $cell->setValignment('center');
        // });
        // $sheet->cell("F$a", function($cell){
        //   $cell->setValue("PASIVA");
        //   $cell->setFontSize(11);
        //   $cell->setAlignment('center');
        //   $cell->setValignment('center');
        // });
        foreach ($data['data1']['aktiva'] as $k => $value) {
          $value2=$data['data2']['aktiva'][$k];
          $sheet->setCellValue("B$i",$value['code']);

          $sheet->cell("C$i", function($cell) use ($value){
            $cell->setValue(menjorokSpasi($value['deep']).$value['name']);
            if ($value['is_base']==1) {
              $cell->setFontWeight('bold');
            }
          });

          $sheet->setCellValue("D$i",($value['is_base']==0?$value['amount']:''));
          $sheet->setCellValue("E$i",($value2['is_base']==0?$value2['amount']:''));
          $i++;
        }
        foreach ($data['data1']['pasiva'] as $k => $value) {
          $value2=$data['data2']['pasiva'][$k];
          $sheet->setCellValue("F$o",$value['code']);

          $sheet->cell("G$o", function($cell) use ($value){
            $cell->setValue(menjorokSpasi($value['deep']).$value['name']);
            if ($value['is_base']==1) {
              $cell->setFontWeight('bold');
            }
          });

          $sheet->setCellValue("H$o",($value['is_base']==0?$value['amount']:''));
          $sheet->setCellValue("I$o",($value2['is_base']==0?$value2['amount']:''));
          $o++;
        }
        if ($i>$o) {
          $dipakai=$i;
        } else {
          $dipakai=$o;
        }
        $dipakai++;
        $sheet->setCellValue("C$dipakai","TOTAL AKTIVA");
        $sheet->setCellValue("D$dipakai","=SUM(D$fori:D$i)");
        $sheet->setCellValue("E$dipakai","=SUM(E$fori:E$i)");
        $sheet->setCellValue("G$dipakai","TOTAL PASIVA");
        $sheet->setCellValue("H$dipakai","=SUM(H$foro:H$o)");
        $sheet->setCellValue("I$dipakai","=SUM(I$foro:I$o)");

      });
    })->export('xls');

  }

  public function export_posisi_keuangan_bkp(Request $request)
  {
    // dd($request);
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=Carbon::parse($request->start_date)->format('Y-m-d');
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr2.=" AND journals.company_id = $request->company_id";
    }
    $default=AccountDefault::first();
    $SQL_laba_tahun_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE
      accounts.group_report = 2
      AND journals.date_transaction < '$dt_start'
    ".(isset($request->company_id)?' and journals.company_id = '.$request->company_id:'');
    $SQL_laba_bulan_berjalan="
    SELECT
      IFNULL( SUM( journal_details.credit ) - SUM( journal_details.debet ), 0 ) AS amount
    FROM
      journal_details
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      LEFT JOIN journals ON journals.id = journal_details.header_id
    WHERE $wr ";

    // dd($sql);
    $lr_tahun_berjalan = DB::select($SQL_laba_tahun_berjalan)[0]->amount;
    $lr_bulan_berjalan = DB::select($SQL_laba_bulan_berjalan)[0]->amount;
    // dd($lr_bulan_berjalan);
    $datas=[];
    $acc_aktiva=Account::where('group_report', 1)->whereBetween('type_id', [1,8])->orderBy('code')->get();
    foreach ($acc_aktiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE $wr AND accounts.id = $value->id
      ";
      $main=DB::select($sql)[0];
      $datas['aktiva'][]=[
        'is_base' => $value->is_base,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
      ];
    }
    $acc_pasiva=Account::where('group_report', 1)->whereBetween('type_id', [9,17])->orderBy('code')->get();
    foreach ($acc_pasiva as $key => $value) {
      $sql="
      SELECT
        IFNULL(SUM(journal_details.debet),0) as debet,
        IFNULL(SUM(journal_details.credit),0) as credit
      FROM journal_details
      LEFT JOIN journals ON journals.id = journal_details.header_id
      LEFT JOIN accounts ON accounts.id = journal_details.account_id
      WHERE $wr AND accounts.id = $value->id
      ";
      $main=DB::select($sql)[0];
      if ($value->id==$default->laba_tahun_berjalan) {
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_tahun_berjalan
        ];
      } elseif ($value->id==$default->laba_bulan_berjalan) {
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => $lr_bulan_berjalan
        ];
      } else {
        $datas['pasiva'][]=[
          'is_base' => $value->is_base,
          'deep' => $value->deep,
          'code' => $value->code,
          'name' => $value->name,
          'amount' => ($value->jenis==1?$main->debet-$main->credit:$main->credit-$main->debet)
        ];
      }
    }
    // dd($datas);
    $data = [
      'data' => $datas,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'company' => Company::find($request->company_id)
    ];
    // dd($data);
    // return view('export.posisi_keuangan',$data);
    return PDF::loadView('export.posisi_keuangan', $data)->stream();
  }
  public function outstanding_debt()
  {
    $data['company'] = companyAdmin(auth()->id());
    $data['customer'] = Contact::where('is_pelanggan', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function export_outstanding_debt(Request $request)
  {
    $wr="1=1";
    if (!empty($request->company_id)) {
      $data['company'] = Company::find($request->company_id);
      $wr.=" and receivables.company_id = $request->company_id";
    } else {
      $user = DB::table('users')->where('id', auth()->id())->first();
      if ($user->is_admin) {
        $data['company'] = Company::all();
      } else {
        $data['company'] = Company::where('id', $user->company_id)->get();
        $wr.=" and receivables.company_id = $user->company_id";
      }
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and receivables.date_transaction between '$start' and '$end'";
    }
    if ($request->customer_id) {
      $wr.=" and receivables.contact_id = $request->customer_id";
    }
    $source=DB::table('receivables')
    ->leftJoin('contacts','contacts.id','=','receivables.contact_id')
    ->leftJoin('companies','companies.id','=','receivables.company_id')
    ->leftJoin('type_transactions','type_transactions.id','=','receivables.type_transaction_id')
    ->leftJoin('users','receivables.created_by','=','users.id')
    ->whereRaw($wr)
    ->whereRaw('(receivables.debet-receivables.credit > 0)')
    ->select([
      'companies.name as company',
      'contacts.name as contact',
      'type_transactions.name as type_transaction',
      'receivables.code',
      'receivables.date_transaction',
      'receivables.date_tempo',
      'receivables.debet',
      'receivables.credit',
      'receivables.updated_at',
      'receivables.description',
      'users.name as username',
      DB::raw('(receivables.debet-receivables.credit) as sisa'),
      DB::raw('DATEDIFF(receivables.date_tempo,receivables.date_transaction) as due_days'),
      DB::raw('(receivables.debet-receivables.credit)/receivables.debet*100 as percent'),
      DB::raw("IF(DATEDIFF(receivables.date_tempo,receivables.date_transaction) <= 30,'V','') as day_30"),
      DB::raw("IF(DATEDIFF(receivables.date_tempo,receivables.date_transaction) between 31 and 60,'V','') as day_60"),
      DB::raw("IF(DATEDIFF(receivables.date_tempo,receivables.date_transaction) between 61 and 90,'V','') as day_90"),
      DB::raw("IF(DATEDIFF(receivables.date_tempo,receivables.date_transaction) > 90,'V','') as day_more_90"),
    ])->get();

    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $source;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit->debet == 0 && $unit->credit == 0 ) {
            unset( $source[$key] );
          }
      }
    }

    // dd($source);
    $data['source']=$source;
    $data['type_transaction'] = TypeTransaction::where('slug', 'giro')->first();
    $data['request'] = (object)$request->input();
    return PDF::loadView('export.outstanding_piutang_new', $data)->setPaper('A4', 'landscape')->stream();
  }
  public function outstanding_credit()
  {
    $data['company'] = companyAdmin(auth()->id());
    $data['supplier'] = Contact::where('is_supplier', 1)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function export_outstanding_credit(Request $request)
  {
    $wr="1=1";
    if (!empty($request->company_id)) {
      $data['company'] = Company::find($request->company_id);
      $wr.=" and payables.company_id = $request->company_id";
    } else {
      $user = DB::table('users')->where('id', auth()->id())->first();
      if ($user->is_admin) {
        $data['company'] = Company::all();
      } else {
        $data['company'] = Company::where('id', $user->company_id)->get();
        $wr.=" and payables.company_id = $user->company_id";
      }
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and payables.date_transaction between '$start' and '$end'";
    }
    if ($request->customer_id) {
      $wr.=" and payables.contact_id = $request->customer_id";
    }
    $source=DB::table('payables')
    ->leftJoin('contacts','contacts.id','=','payables.contact_id')
    ->leftJoin('companies','companies.id','=','payables.company_id')
    ->leftJoin('type_transactions','type_transactions.id','=','payables.type_transaction_id')
    ->leftJoin('users','payables.created_by','=','users.id')
    ->whereRaw($wr)
    ->whereRaw('(payables.credit-payables.debet > 0)')
    ->select([
      'companies.name as company',
      'contacts.name as contact',
      'type_transactions.name as type_transaction',
      'payables.code',
      'payables.date_transaction',
      'payables.date_tempo',
      'payables.debet',
      'payables.credit',
      'payables.updated_at',
      'payables.description',
      'users.name as username',
      DB::raw('(payables.credit-payables.debet) as sisa'),
      DB::raw('DATEDIFF(payables.date_tempo,payables.date_transaction) as due_days'),
      DB::raw('(payables.debet-payables.credit)/payables.debet*100 as percent'),
      DB::raw("IF(DATEDIFF(payables.date_tempo,payables.date_transaction) <= 30,'V','') as day_30"),
      DB::raw("IF(DATEDIFF(payables.date_tempo,payables.date_transaction) between 31 and 60,'V','') as day_60"),
      DB::raw("IF(DATEDIFF(payables.date_tempo,payables.date_transaction) between 61 and 90,'V','') as day_90"),
      DB::raw("IF(DATEDIFF(payables.date_tempo,payables.date_transaction) > 90,'V','') as day_more_90"),
    ])->get();

    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $source;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit->debet == 0 && $unit->credit == 0 ) {
            unset( $source[$key] );
          }
      }
    }
    $data['source']=$source;
    $data['type_transaction'] = TypeTransaction::where('slug', 'giro')->first();
    $data['request'] = (object)$request->input();
    return PDF::loadView('export.outstanding_hutang_new', $data)->setPaper('A4', 'landscape')->stream();
  }

  public function laba_rugi_perbandingan()
  {
    $data['company']=companyAdmin(auth()->id());
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function export_laba_rugi_perbandingan(Request $request)
  {
    $company_id = '';
    $start = '';
    $end = '';
    if($request->company_id){
      $company_id = $request->company_id;
    }
    if(isset($request->start_date) && isset($request->end_date)) {
      $start = $request->start_date;
      $end = $request->end_date;
    }
    $datas = self::data_laba_rugi($company_id,$start,$end);
    $company_id = '';
    $start = '';
    $end = '';
    if($request->company_id_perbandingan){
      $company_id = $request->company_id_perbandingan;
    }
    if(isset($request->start_date_perbandingan) && isset($request->start_date_perbandingan)) {
      $start = $request->start_date_perbandingan;
      $end = $request->start_date_perbandingan;
    }

    $perbandingan = self::data_laba_rugi($company_id,$start,$end);
    if (!empty($request->company_id)) {
      $company = Company::find($request->company_id);
    } else {
      $user = DB::table('users')->where('id', auth()->id())->first();
      if ($user->is_admin) {
        $company = Company::all();
      } else {
        $company = Company::where('id', $user->company_id)->get();
      }
    }
    if (!empty($request->company_id_perbandingan)) {
      $company_perbandingan = Company::find($request->company_id_perbandingan);
    } else {
      $user = DB::table('users')->where('id', auth()->id())->first();
      if ($user->is_admin) {
        $company_perbandingan = Company::all();
      } else {
        $company_perbandingan = Company::where('id', $user->company_id)->get();
      }
    }

    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    $units = $datas;
    if($prevent_zero == 'true') {
      foreach ($units as $key => $unit) {
          if( $unit['is_base'] == 0 && $unit['amount'] == 0 ) {
            unset( $datas[$key] );
          }
      }
    }
    $data = [
      'data' => $datas,
      'perbandingan'=>$perbandingan,
      'request'=>(object)$request->input(),
      'company'=>$company,
      'company_perbandingan'=>$company_perbandingan,
    ];
    // return view('export.laba_rugi_banding', $data);
    return PDF::loadView('export.laba_rugi_banding', $data)->setPaper('A4')->stream();
  }
  public static function data_laba_rugi($company_id,$start,$end){
    $wr = "1=1";
    $wr2 = "";
    if ((isset($start) && $start!= '') && isset($end) && $end!='') {
      $wr .= " AND journals.date_transaction BETWEEN '" . date('Y-m-d', strtotime($start)) . "' AND '" . date('Y-m-d', strtotime($end)) . "'";
      $dt_start = date('Y-m-d', strtotime($start));
    } else {
      $dt_start = Carbon::parse('first day of this month')->format('Y-m-d');
      $wr .= " AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($company_id) && $company_id!='') {
      $wr .= " AND journals.company_id = $company_id";
      $wr2 .= " AND journals.company_id = $company_id";
    }
    $wr1 = "";
    // if (isset($request->is_not_zero)) {
    //   $wr1 .= " WHERE
    //     ( det.jml_debet != 0
    //     OR det.jml_credit != 0 ) ";
    // }
    $parent = Account::where('group_report', 2)->orderBy('code')->get();
    $datas = [];
    foreach ($parent as $value) {
      $sql = "
      SELECT
        IFNULL(SUM( journal_details.credit ) - SUM( journal_details.debet ),0) AS amount
      FROM
        journal_details
        LEFT JOIN accounts ON accounts.id = journal_details.account_id
        LEFT JOIN journals ON journals.id = journal_details.header_id
      WHERE
        journal_details.account_id = $value->id AND $wr AND journals.status = 3 AND journals.type_transaction_id != 53
      ";
      $tarik = DB::select($sql)[0];
      $datas[] = [
        'id' => $value->id,
        'deep' => $value->deep,
        'code' => $value->code,
        'name' => $value->name,
        'jenis' => $value->jenis,
        'is_base' => $value->is_base,
        'parent' => $value->parent_id,
        'amount' => $tarik->amount
      ];
    }
    return $datas;
  }

  public function export_arus_kas(Request $request)
  {
    // dd($request);
    $aktivitas=[
      1=> 'Aktivitas Operasional',
      2=> 'Aktivitas Investasi',
      3=> 'Aktivitas Pendanaan',
    ];
    $wr="1=1";
    $wr1="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    $wr1.=" AND journals.date_transaction < '$dt_start'";
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr1.=" AND journals.company_id = $request->company_id";
    }

    $sql = "
    SELECT
      id,
      code,
      name,
      kategori,
      jenis,
      is_base,
      ifnull(IF(jenis=1,(Y.db-Y.cr),(Y.cr-Y.db)),0) as total
    FROM
      cash_categories
    LEFT JOIN (select cash_category_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 and journal_details.cash_category_id is not null and $wr group by journal_details.cash_category_id) Y on Y.cash_category_id = cash_categories.id
    ORDER BY
    CODE ASC
    ";
    // dd($sql);
    $datas = DB::select($sql);
    // dd($datas);
    $sql_kas="
    SELECT
      code,
      name,
      ifnull(if(jenis=1,(db-cr),(cr-db)),0) as total
    FROM
      accounts
    LEFT JOIN (select journal_details.account_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 $wr1 group by journal_details.account_id) Y on Y.account_id = accounts.id
    WHERE
      no_cash_bank IN ( 1, 2 ) and is_base = 0
    ";
    $data_kas=DB::select($sql_kas);
    // Apakah menampikan data yang bernilai 0 atau tidak
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
      $units = $datas;
      foreach ($units as $key => $unit) {
        if( $unit->total == 0 ) {
          unset( $datas[$key] );
        }
      }

      $units = $data_kas;
      foreach ($units as $key => $unit) {
        if( $unit->total == 0 ) {
          unset( $data_kas[$key] );
        }
      }
    }
    // =======================================================================
    $data = [
      'data' => $datas,
      'data_kas' => $data_kas,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'aktivitas' => $aktivitas,
      'company' => Company::find($request->company_id)
    ];
    return PDF::loadView('export.arus_kas', $data)->stream();
  }
  public function export_arus_kas_perbandingan(Request $request)
  {
    // dd($request);
    $aktivitas=[
      1=> 'Aktivitas Operasional',
      2=> 'Aktivitas Investasi',
      3=> 'Aktivitas Pendanaan',
    ];
    $wr="1=1";
    $wr2="1=1";
    $wr1="";
    $wr3="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=date('Y-m-d', strtotime($request->start_date));
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->start_date2) && isset($request->end_date2)) {
      $wr2.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date2))."' AND '".date('Y-m-d', strtotime($request->end_date2))."'";
      $dt_start2=date('Y-m-d', strtotime($request->start_date2));
    } else {
      $dt_start2=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr2.=" AND journals.date_transaction >= '$dt_start2'";
    }
    $wr1.=" AND journals.date_transaction < '$dt_start'";
    $wr3.=" AND journals.date_transaction < '$dt_start2'";
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr1.=" AND journals.company_id = $request->company_id";
    }
    if (isset($request->company_id2)) {
      $wr2.=" AND journals.company_id = $request->company_id2";
      $wr3.=" AND journals.company_id = $request->company_id2";
    }

    $sql = "
    SELECT
      id,
      code,
      name,
      kategori,
      jenis,
      is_base,
      ifnull(IF(jenis=1,(Y.db-Y.cr),(Y.cr-Y.db)),0) as total
    FROM
      cash_categories
    LEFT JOIN (select cash_category_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 and journal_details.cash_category_id is not null and $wr group by journal_details.cash_category_id) Y on Y.cash_category_id = cash_categories.id
    ORDER BY
    CODE ASC
    ";
    $sql2 = "
    SELECT
      id,
      code,
      name,
      kategori,
      jenis,
      is_base,
      ifnull(IF(jenis=1,(Y.db-Y.cr),(Y.cr-Y.db)),0) as total
    FROM
      cash_categories
    LEFT JOIN (select cash_category_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 and journal_details.cash_category_id is not null and $wr2 group by journal_details.cash_category_id) Y on Y.cash_category_id = cash_categories.id
    ORDER BY
    CODE ASC
    ";
    // dd($sql);
    $datas = DB::select($sql);
    $datas2 = DB::select($sql2);
    // dd($datas);
    $sql_kas="
    SELECT
      code,
      name,
      ifnull(if(jenis=1,(db-cr),(cr-db)),0) as total
    FROM
      accounts
    LEFT JOIN (select journal_details.account_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 $wr1 group by journal_details.account_id) Y on Y.account_id = accounts.id
    WHERE
      no_cash_bank IN ( 1, 2 ) and is_base = 0
    ";
    $sql_kas2="
    SELECT
      code,
      name,
      ifnull(if(jenis=1,(db-cr),(cr-db)),0) as total
    FROM
      accounts
    LEFT JOIN (select journal_details.account_id, sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 $wr3 group by journal_details.account_id) Y on Y.account_id = accounts.id
    WHERE
      no_cash_bank IN ( 1, 2 ) and is_base = 0
    ";
    $data_kas=DB::select($sql_kas);
    $data_kas2=DB::select($sql_kas2);

    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
      // Data
      $units = $datas;
      $units2 = $datas2;
      foreach ($units as $key => $unit) {
          $unit2 = $units2[$key];
          if( $unit->is_base == 0 && $unit->total == 0 && $unit2->total == 0 ) {
            unset( $datas[$key] );
            unset( $datas2[$key] );
          }
      }

      // Data
      $units = $data_kas;
      $units2 = $data_kas2;
      foreach ($units as $key => $unit) {
          $unit2 = $units2[$key];
          if( $unit->total == 0 && $unit2->total == 0 ) {
            unset( $data_kas[$key] );
            unset( $data_kas2[$key] );
          }
      }


    }

    $data = [
      'data' => $datas,
      'data2' => $datas2,
      'data_kas' => $data_kas,
      'data_kas2' => $data_kas2,
      'start' => $request->start_date,
      'end' => $request->end_date,
      'start2' => $request->start_date2,
      'end2' => $request->end_date2,
      'aktivitas' => $aktivitas,
      'company' => Company::find($request->company_id),
      'company2' => Company::find($request->company_id2)
    ];
    return PDF::loadView('export.arus_kas_banding', $data)->stream();
  }

  public function export_neraca_lajur(Request $request)
  {
    $wr="1=1";
    $wr2="";
    if (isset($request->start_date) && isset($request->end_date)) {
      $wr.=" AND journals.date_transaction BETWEEN '".date('Y-m-d', strtotime($request->start_date))."' AND '".date('Y-m-d', strtotime($request->end_date))."'";
      $dt_start=Carbon::parse($request->start_date)->format('Y-m-d');
    } else {
      $dt_start=Carbon::parse('first day of this month')->format('Y-m-d');
      $wr.=" AND journals.date_transaction >= '$dt_start'";
    }
    if (isset($request->company_id)) {
      $wr.=" AND journals.company_id = $request->company_id";
      $wr2.=" AND journals.company_id = $request->company_id";
    }

    $sql="
    select
    deep,
    is_base,
    concat(code,' - ',name) as account_name,
    @mutasiD := ifnull(Y.mutasiD,0) varMutD,
    @mutasiK := ifnull(Y.mutasiK,0) varMutK,
    @penyesuaianD := ifnull(Y.penyesuaianD,0) varPenyesuaianD,
    @penyesuaianK := ifnull(Y.penyesuaianK,0) varPenyesuaianK,
    @saldoMentah1 := if(jenis=1,(ifnull(saldo.db,0)-ifnull(saldo.cr,0)),(ifnull(saldo.cr,0)-ifnull(saldo.db,0))) varSaldoMentah1,
    @saldoMentah2 := if(jenis=1,(ifnull(saldo2.db,0)-ifnull(saldo2.cr,0)),(ifnull(saldo2.cr,0)-ifnull(saldo2.db,0))) varSaldoMentah2,
    @mutasiAsli := if(jenis=1,@mutasiD-@mutasiK,@mutasiK-@mutasiD) varMutasiAsli,
    @penyesuaianAsli := if(jenis=1,@penyesuaianD-@penyesuaianK,@penyesuaianK-@penyesuaianD) varPenyesuaianAsli,
    @saldoAsli := if(@saldoMentah1 != 0,@saldoMentah1,@saldoMentah2) as varSaldoAsli,
    @ambilSaldoD := if(group_report=1,if(jenis=1 and @saldoAsli>=0,@saldoAsli,if(jenis=2 and @saldoAsli<0,abs(@saldoAsli),0)),0) as ambilSaldoD,
    @ambilSaldoK := if(group_report=1,if(jenis=2 and @saldoAsli>=0,@saldoAsli,if(jenis=1 and @saldoAsli<0,abs(@saldoAsli),0)),0) as ambilSaldoK,
    @ambilMutasiD := if(jenis=1 and @mutasiAsli>=0,@mutasiAsli,if(jenis=2 and @mutasiAsli<0,abs(@mutasiAsli),0)) as ambilMutasiD,
    @ambilMutasiK := if(jenis=2 and @mutasiAsli>=0,@mutasiAsli,if(jenis=1 and @mutasiAsli<0,abs(@mutasiAsli),0)) as ambilMutasiK,
    @ambilNsD := @ambilSaldoD+@ambilMutasiD as ambilNsD,
    @ambilNsK := @ambilSaldoK+@ambilMutasiK as ambilNsK,
    @ambilPenyesuaianD := if(jenis=1 and @penyesuaianAsli>=0,@penyesuaianAsli,if(jenis=2 and @penyesuaianAsli<0,abs(@penyesuaianAsli),0)) as ambilPenyesuaianD,
    @ambilPenyesuaianK := if(jenis=2 and @penyesuaianAsli>=0,@penyesuaianAsli,if(jenis=1 and @penyesuaianAsli<0,abs(@penyesuaianAsli),0)) as ambilPenyesuaianK,
    @ambilNsPD := @ambilNsD+@ambilPenyesuaianD as ambilNsPD,
    @ambilNsPK := @ambilNsK+@ambilPenyesuaianK as ambilNsPK,
    @ambilLRD := if(group_report=2,@ambilNsPD,0) as ambilLRD,
    @ambilLRK := if(group_report=2,@ambilNsPK,0) as ambilLRK,
    @ambilPSD := if(group_report=1,@ambilNsPD,0) as ambilPSD,
    @ambilPSK := if(group_report=1,@ambilNsPK,0) as ambilPSK
    from accounts
    left join (
      select journal_details.account_id,sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id left join type_transactions on type_transactions.id = journals.type_transaction_id where type_transactions.is_saldo = 1 and journals.status = 3 and $wr group by journal_details.account_id
    ) saldo on saldo.account_id = accounts.id
    left join (
      select journal_details.account_id,sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id left join type_transactions on type_transactions.id = journals.type_transaction_id where journals.status = 3 and journals.date_transaction < '$dt_start' $wr2 group by journal_details.account_id
    ) saldo2 on saldo2.account_id = accounts.id
    left join (
      select
      journal_details.account_id,
      sum(if(type_transactions.is_journal=1,journal_details.debet,0)) as mutasiD,
      sum(if(type_transactions.is_penyesuaian=1,journal_details.debet,0)) as penyesuaianD,
      sum(if(type_transactions.is_journal=1,journal_details.credit,0)) as mutasiK,
      sum(if(type_transactions.is_penyesuaian=1,journal_details.credit,0)) as penyesuaianK
      from journal_details
      left join journals on journals.id = journal_details.header_id
      left join type_transactions on type_transactions.id = journals.type_transaction_id
      where journals.status = 3 and $wr
      group by journal_details.account_id
    ) Y on Y.account_id = accounts.id
    order by code asc
    ";
    $data=DB::select($sql);

    // Menghitung subtotal
    foreach($data AS $unit) {
      $subtotal = $unit->ambilSaldoD + $unit->ambilSaldoK + $unit->ambilMutasiD + $unit->ambilMutasiK + $unit->ambilNsD + $unit->ambilNsK + $unit->ambilPenyesuaianD + $unit->ambilPenyesuaianK + $unit->ambilNsPD + $unit->ambilNsPK + $unit->ambilLRD + $unit->ambilLRK + $unit->ambilPSD + $unit->ambilPSK;

      $unit->subtotal = $subtotal;
    }

    // Apakah menampikan data yang bernilai 0 atau tidak
    $prevent_zero = $request->prevent_zero;
    $prevent_zero = $request->prevent_zero != null ?  $prevent_zero : 'false';
    if($prevent_zero == 'true') {
      $units = $data;
      foreach ($units as $key => $unit) {
        if($unit->subtotal == 0) {
          unset( $data[$key] );
        }
      }
    }
    // =======================================================================

    // dd($data);
    Excel::create('Laporan Neraca Lajur - '.Carbon::now(), function($excel) use ($data,$request) {
      $excel->getDefaultStyle()
      ->getAlignment()
      ->applyFromArray(array(
          'wrap'    => TRUE
      ));
      $excel->sheet('Data', function($sheet) use ($data,$request){
        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
          'fill' => [
            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFFFFF']
          ]
        ]);
        $sheet->setWidth([
          'B' => 70,
          'C' => 20,
          'D' => 20,
          'E' => 20,
          'F' => 20,
          'G' => 20,
          'H' => 20,
          'I' => 20,
          'J' => 20,
          'K' => 20,
          'L' => 20,
          'M' => 20,
          'N' => 20,
          'O' => 20,
          'P' => 20,
        ]);

        // $sheet->cells('A1:F1', function($cells){
        //   $cells->setFontWeight('bold');
        // });
        $sheet->setColumnFormat([
          'B' => '@',
          'C' => '#,##0.00',
          'D' => '#,##0.00',
          'E' => '#,##0.00',
          'F' => '#,##0.00',
          'G' => '#,##0.00',
          'H' => '#,##0.00',
          'I' => '#,##0.00',
          'J' => '#,##0.00',
          'K' => '#,##0.00',
          'L' => '#,##0.00',
          'M' => '#,##0.00',
          'N' => '#,##0.00',
          'O' => '#,##0.00',
          'P' => '#,##0.00'
        ]);

        $a=2;
        $sheet->mergeCells("B$a:P$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("SOLOG");
          $cell->setFontSize(16);
          $cell->setFontWeight('bold');
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:P$a");
        $sheet->cell("B$a", function($cell){
          $cell->setValue("NERACA LAJUR");
          $cell->setFontSize(13);
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->mergeCells("B$a:P$a");
        $sheet->cell("B$a", function($cell) use ($request){
          $company=DB::table('companies')->where('id', $request->company_id)->first();
          $cell->setValue($company->name??'Semua Cabang');
          $cell->setFontSize(11);
          $cell->setAlignment('center');
        });
        $a++;
        $sheet->cells("B$a:P".($a+1), function($cell){
          $cell->setFontWeight('bold');
          $cell->setAlignment('center');
          $cell->setVAlignment('center');
        });
        $sheet->mergeCells("C$a:D$a");
        $sheet->mergeCells("E$a:F$a");
        $sheet->mergeCells("G$a:H$a");
        $sheet->mergeCells("I$a:J$a");
        $sheet->mergeCells("K$a:L$a");
        $sheet->mergeCells("M$a:N$a");
        $sheet->mergeCells("O$a:P$a");
        $sheet->setCellValue("B$a",'Nama Akun');
        $sheet->setCellValue("C$a",'Saldo Awal');
        $sheet->setCellValue("E$a",'Mutasi');
        $sheet->setCellValue("G$a",'Neraca Saldo');
        $sheet->setCellValue("I$a",'Penyesuaian');
        $sheet->setCellValue("K$a",'Neraca Saldo Disesuaikan');
        $sheet->setCellValue("M$a",'Laba Rugi');
        $sheet->setCellValue("O$a",'Posisi Keuangan');
        $sheet->mergeCells("B$a:B".($a+1));
        $a++;
        $sheet->setCellValue("C$a",'Debet');
        $sheet->setCellValue("D$a",'Kredit');
        $sheet->setCellValue("E$a",'Debet');
        $sheet->setCellValue("F$a",'Kredit');
        $sheet->setCellValue("G$a",'Debet');
        $sheet->setCellValue("H$a",'Kredit');
        $sheet->setCellValue("I$a",'Debet');
        $sheet->setCellValue("J$a",'Kredit');
        $sheet->setCellValue("K$a",'Debet');
        $sheet->setCellValue("L$a",'Kredit');
        $sheet->setCellValue("M$a",'Debet');
        $sheet->setCellValue("N$a",'Kredit');
        $sheet->setCellValue("O$a",'Debet');
        $sheet->setCellValue("P$a",'Kredit');
        $a++;
        $p=$a;
        foreach ($data as $key => $value) {
          // $sheet->setCellValue("B$a",$value->account_name);
          $sheet->cell("B$a", function($cell) use ($value){
            $cell->setValue(menjorokSpasi($value->deep).$value->account_name);
            if ($value->is_base==1) {
              $cell->setFontWeight('bold');
            }
          });

          $sheet->setCellValue("C$a",$value->ambilSaldoD);
          $sheet->setCellValue("D$a",$value->ambilSaldoK);
          $sheet->setCellValue("E$a",$value->ambilMutasiD);
          $sheet->setCellValue("F$a",$value->ambilMutasiK);
          $sheet->setCellValue("G$a",$value->ambilNsD);
          $sheet->setCellValue("H$a",$value->ambilNsK);
          $sheet->setCellValue("I$a",$value->ambilPenyesuaianD);
          $sheet->setCellValue("J$a",$value->ambilPenyesuaianK);
          $sheet->setCellValue("K$a",$value->ambilNsPD);
          $sheet->setCellValue("L$a",$value->ambilNsPK);
          $sheet->setCellValue("M$a",$value->ambilLRD);
          $sheet->setCellValue("N$a",$value->ambilLRK);
          $sheet->setCellValue("O$a",$value->ambilPSD);
          $sheet->setCellValue("P$a",$value->ambilPSK);
          $a++;
        }
        $sheet->cells("C$a:P$a", function($cell){
          $cell->setFontWeight('bold');
        });
        $sheet->setCellValue("C$a","=SUM(C$p:C".($a-1).")");
        $sheet->setCellValue("D$a","=SUM(D$p:D".($a-1).")");
        $sheet->setCellValue("E$a","=SUM(E$p:E".($a-1).")");
        $sheet->setCellValue("F$a","=SUM(F$p:F".($a-1).")");
        $sheet->setCellValue("G$a","=SUM(G$p:G".($a-1).")");
        $sheet->setCellValue("H$a","=SUM(H$p:H".($a-1).")");
        $sheet->setCellValue("I$a","=SUM(I$p:I".($a-1).")");
        $sheet->setCellValue("J$a","=SUM(J$p:J".($a-1).")");
        $sheet->setCellValue("K$a","=SUM(K$p:K".($a-1).")");
        $sheet->setCellValue("L$a","=SUM(L$p:L".($a-1).")");
        $sheet->setCellValue("M$a","=SUM(M$p:M".($a-1).")");
        $sheet->setCellValue("N$a","=SUM(N$p:N".($a-1).")");
        $sheet->setCellValue("O$a","=SUM(O$p:O".($a-1).")");
        $sheet->setCellValue("P$a","=SUM(P$p:P".($a-1).")");

      });
    })->export('xls');

  }

}

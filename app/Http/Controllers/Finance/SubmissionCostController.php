<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\JobOrderCost;
use App\Model\ManifestCost;
use App\Model\SubmissionCost;
use App\Model\CostType;
use App\Model\Contact;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
use App\Model\Account;
use DB;
use Response;
use Carbon\Carbon;

class SubmissionCostController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $sql="
      SELECT
        submission_costs.id,
        submission_costs.type_submission,
        submission_costs.relation_cost_id,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          manifests.code
        WHEN 1 THEN
          job_orders.code
        WHEN 4 THEN
          cash_transactions.code
        ELSE
          '-'
        END) as codes,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          '-'
        WHEN 1 THEN
          CONCAT(job_orders.aju_number,' / ',job_orders.no_bl)
        WHEN 4 THEN
          '-'
        ELSE
          '-'
        END) as aju_bl,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          containers.container_no
        WHEN 1 THEN
          '-'
        WHEN 4 THEN
          '-'
        ELSE
          '-'
        END) as container,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          customer.name
        WHEN 1 THEN
          customer.name
        WHEN 4 THEN
          '-'
        ELSE
          '-'
        END) as customer,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          manifest_costs.total_price
        WHEN 1 THEN
          job_order_costs.total_price
        WHEN 4 THEN
          cash_transactions.total
        ELSE
          0
        END) as amount,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          manifest_costs.before_revision_cost
        WHEN 1 THEN
          job_order_costs.before_revision_cost
        WHEN 4 THEN
          0
        ELSE
          0
        END) as before_revision_cost,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          manifest_costs.quotation_costs
        WHEN 1 THEN
          job_order_costs.quotation_costs
        WHEN 4 THEN
          0
        ELSE
          0
        END) as quotation_costs,
        (CASE submission_costs.type_submission
        WHEN 2 THEN
          '-'
        WHEN 1 THEN
          approve_jo.name
        WHEN 4 THEN
          '-'
        ELSE
          '-'
        END) as approve_by,
        submission_costs.date_submission,
        submission_costs.description,
        submission_costs.status,
        submission_costs.revision_date,
        companies.name as cname,
        approve.name as user_approve
      FROM
        submission_costs
        LEFT JOIN users as approve ON approve.id = submission_costs.approve_by
        LEFT JOIN companies ON companies.id = submission_costs.company_id

        LEFT JOIN manifest_costs ON manifest_costs.id = submission_costs.relation_cost_id
        LEFT JOIN manifests ON manifests.id = manifest_costs.header_id
        LEFT JOIN containers ON manifests.container_id = containers.id

        LEFT JOIN job_order_costs ON job_order_costs.id = submission_costs.relation_cost_id
        LEFT JOIN job_orders ON job_order_costs.header_id = job_orders.id
        LEFT JOIN contacts as customer ON job_orders.customer_id = customer.id

        LEFT JOIN users as approve_jo ON job_order_costs.approve_by = approve_jo.id

        LEFT JOIN cash_transactions ON submission_costs.relation_cost_id = cash_transactions.id
      WHERE
        submission_costs.id = $id LIMIT 1
      ";
      $item['item']=DB::select($sql)[0];
      $item['cash']=CashTransaction::with('account')->where('id', $item['item']->relation_cost_id)->first();
      $item['cash_detail']=CashTransactionDetail::with('account')->where('header_id', $item['item']->relation_cost_id)->get();
      return Response::json($item, 200, [], JSON_NUMERIC_CHECK);
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

    public function approve($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      if ($sc->type_submission==1) {
        // Job Order Cost

        $joc=JobOrderCost::find($sc->relation_cost_id);
        if ($joc->before_revision_cost>0) {
          $joc->update(['status' => 3]);
          $sc->update([
            'status' => 2,
            'approve_revision_by' => ($sc->status==5?auth()->id():null),
            'date_approve_revision' => ($sc->status==5?Carbon::now():null),
          ]);
          DB::commit();
          return Response::json(null);
        }
        // $ctype=CostType::find($joc->cost_type_id);
        // $ven=Contact::find($joc->vendor_id);
        // $j=Journal::create([
        //   'company_id' => $sc->company_id,
        //   'type_transaction_id' => $sc->type_transaction_id,
        //   'date_transaction' => $joc->header->shipment_date,
        //   'created_by' => auth()->id(),
        //   'code' => $joc->header->code,
        //   'status' => 2,
        //   'description' => "Pengajuan Biaya Job Order - ".$joc->header->code??'',
        //   'debet' => 0,
        //   'credit' => 0,
        // ]);
        // //jika accrual
        // if ($ctype->type==1) {
        //   $akun=$ctype->akun_uang_muka;
        // } else {
        //   $akun=$ctype->akun_biaya;
        // }
        // JournalDetail::create([
        //   'header_id' => $j->id,
        //   'account_id' => $akun,
        //   'debet' => $joc->total_price,
        //   'credit' => 0,
        // ]);
        // JournalDetail::create([
        //   'header_id' => $j->id,
        //   'account_id' => $ctype->akun_kas_hutang,
        //   'cash_category_id' => $ctype->cash_category_id,
        //   'debet' => 0,
        //   'credit' => $joc->total_price,
        // ]);
        // // cek jika kas / hutang dari no_cash_bank
        // $account=Account::find($ctype->akun_kas_hutang);
        // if (in_array($account->no_cash_bank,[1,2])) { //cek account kas = 1 atau bank = 2
        //   //simpan di transaksi kas
        //   $ct=CashTransaction::create([
        //     'company_id' => $sc->company_id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'code' => $joc->header->code,
        //     'reff' => $joc->header->code,
        //     'jenis' => 2,
        //     'type' => $account->no_cash_bank,
        //     'total' => $joc->total_price,
        //     'account_id' => $account->id,
        //     'date_transaction' => $joc->header->shipment_date,
        //     'status_cost' => 3,
        //     'is_cut' => 1,
        //     'created_by' => auth()->id(),
        //     'journal_id' => $j->id,
        //   ]);

        //   CashTransactionDetail::create([
        //     'header_id' => $ct->id,
        //     'account_id' => $akun,
        //     'contact_id' => $joc->vendor_id,
        //     'amount' => $joc->total_price,
        //     'jenis' => 1
        //   ]);
        //   $cash_transaction_id=$ct->id;
        // } else {
        //   $p=Payable::create([
        //     'company_id' => $sc->company_id,
        //     'contact_id' => $joc->vendor_id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'created_by' => auth()->id(),
        //     'journal_id' => $j->id,
        //     'code' => $joc->header->code,
        //     'date_transaction' => $joc->header->shipment_date,
        //     'date_tempo' => $ven->term_of_payment??Carbon::now(),
        //     'debet' => 0,
        //     'credit' => $joc->total_price,
        //   ]);
        //   $payd=PayableDetail::create([
        //     'header_id' => $p->id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'code' => $joc->header->code,
        //     'date_transaction' => $joc->header->shipment_date,
        //     'debet' => 0,
        //     'credit' => $joc->total_price,
        //   ]);
        //   $payable_id=$p->id;
        // }
        // $joc->update(['status' => 3]);
        $joc->update(['status' => 3]);

      } elseif ($sc->type_submission==2) {
        // Manifest Cost
        $mc=ManifestCost::find($sc->relation_cost_id);
        if ($mc->before_revision_cost>0) {
          $mc->update(['status' => 3]);
          $sc->update([
            'status' => 2,
            'approve_revision_by' => ($sc->status==5?auth()->id():null),
            'date_approve_revision' => ($sc->status==5?Carbon::now():null),
          ]);
          DB::commit();
          return Response::json(null);
        }
        $sql="SELECT DISTINCT job_order_details.header_id as jo_id FROM manifest_details left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id where manifest_details.header_id = $mc->header_id";
        $countDistict=DB::select($sql);
        $total=count($countDistict);
        if ($sc->status==5) {
          JobOrderCost::where('manifest_cost_id', $mc->id)->delete();
        }
        foreach ($countDistict as $key => $value) {
          JobOrderCost::create([
            'header_id' => $value->jo_id,
            'cost_type_id' => $mc->cost_type_id,
            'transaction_type_id' => 21,
            'vendor_id' => $mc->vendor_id,
            'manifest_id' => $mc->header_id,
            'manifest_cost_id' => $mc->id,
            'qty' => $mc->qty,
            'price' => ($mc->price/$total),
            'total_price' => $mc->qty*($mc->price/$total),
            'description' => $mc->description,
            'create_by' => auth()->id(),
            'status' => 3
          ]);
        }

        //backup versi awal
        // $joc=ManifestCost::find($sc->relation_cost_id);
        // $ctype=CostType::find($joc->cost_type_id);
        // $ven=Contact::find($joc->vendor_id);
        // $j=Journal::create([
        //   'company_id' => $sc->company_id,
        //   'type_transaction_id' => $sc->type_transaction_id,
        //   'date_transaction' => $joc->header->date_manifest,
        //   'created_by' => auth()->id(),
        //   'code' => $joc->header->code,
        //   'status' => 2,
        //   'description' => "Pengajuan Biaya Packing List - ".$joc->header->code??'',
        //   'debet' => 0,
        //   'credit' => 0,
        // ]);
        // //jika accrual
        // if ($ctype->type==1) {
        //   $akun=$ctype->akun_uang_muka;
        // } else {
        //   $akun=$ctype->akun_biaya;
        // }
        // JournalDetail::create([
        //   'header_id' => $j->id,
        //   'account_id' => $akun,
        //   'debet' => $joc->total_price,
        //   'credit' => 0,
        // ]);
        // JournalDetail::create([
        //   'header_id' => $j->id,
        //   'account_id' => $ctype->akun_kas_hutang,
        //   'cash_category_id' => $ctype->cash_category_id,
        //   'debet' => 0,
        //   'credit' => $joc->total_price,
        // ]);
        // // cek jika kas / hutang dari no_cash_bank
        // $account=Account::find($ctype->akun_kas_hutang);
        // if (in_array($account->no_cash_bank,[1,2])) { //cek kas = 1 / bank = 2
        //   //simpan di transaksi kas
        //   $ct=CashTransaction::create([
        //     'company_id' => $sc->company_id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'code' => $joc->header->code,
        //     'reff' => $joc->header->code,
        //     'jenis' => 2,
        //     'type' => $account->no_cash_bank,
        //     'total' => $joc->total_price,
        //     'account_id' => $ctype->cash_category_id,
        //     'date_transaction' => $joc->header->date_manifest,
        //     'status_cost' => 3,
        //     'created_by' => auth()->id(),
        //     'journal_id' => $j->id,
        //   ]);

        //   CashTransactionDetail::create([
        //     'header_id' => $ct->id,
        //     'account_id' => $akun,
        //     'contact_id' => $joc->vendor_id,
        //     'amount' => $joc->total_price,
        //     'jenis' => 1
        //   ]);
        //   $cash_transaction_id=$ct->id;
        // } else {
        //   $p=Payable::create([
        //     'company_id' => $sc->company_id,
        //     'contact_id' => $joc->vendor_id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'created_by' => auth()->id(),
        //     'journal_id' => $j->id,
        //     'code' => $joc->header->code,
        //     'date_transaction' => $joc->header->date_manifest,
        //     'date_tempo' => $ven->term_of_payment??Carbon::now(),
        //     'debet' => 0,
        //     'credit' => $joc->total_price,
        //   ]);
        //   $payd=PayableDetail::create([
        //     'header_id' => $p->id,
        //     'type_transaction_id' => $sc->type_transaction_id,
        //     'code' => $joc->header->code,
        //     'date_transaction' => $joc->header->date_manifest,
        //     'debet' => 0,
        //     'credit' => $joc->total_price,
        //   ]);
        //   $payable_id=$p->id;
        // }
        //end backup 2 agustus 2019

        $joc->update(['status' => 3]);

      } elseif ($sc->type_submission==4) {
        // Transaksi Kas
        $ct=CashTransaction::find($sc->relation_cost_id);
        $ct->update([
          'status_cost' => 3
        ]);
      }
      $sc->update([
        'status' => 2,
        'approve_by' => auth()->id(),
        'journal_id' => $j->id??null,
        'payable_id' => $payable_id??null,
        'cash_transaction_id' => $cash_transaction_id??null,
        'approve_revision_by' => ($sc->status==5?auth()->id():null),
        'date_approve_revision' => ($sc->status==5?Carbon::now():null),
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function cancel_approve($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      if ($sc->type_submission==1) {
        // Job Order Cost
        JobOrderCost::find($sc->relation_cost_id)->update([
          'status' => 1
        ]);
      } elseif ($sc->type_submission==2) {
        // Manifest Cost
        ManifestCost::find($sc->relation_cost_id)->update([
          'status' => 1
        ]);
      } elseif ($sc->type_submission==4) {
        // Transaksi Kas
        CashTransaction::find($sc->relation_cost_id)->update([
          'status_cost' => 1
        ]);
      }
      SubmissionCost::find($sc->id)->delete();
      Journal::where('id',$sc->journal_id)->delete();
      Payable::where('id',$sc->payable_id)->delete();
      CashTransaction::where('id',$sc->cash_transaction_id)->delete();
      DB::commit();
    }

    public function cancel_posting($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      if ($sc->type_submission==1) {
        // Job Order Cost
        JobOrderCost::find($sc->relation_cost_id)->update([
          'status' => 3
        ]);
      } elseif ($sc->type_submission==2) {
        // Manifest Cost
        ManifestCost::find($sc->relation_cost_id)->update([
          'status' => 3
        ]);
      } elseif ($sc->type_submission==4) {
        // Transaksi Kas
        CashTransaction::find($sc->relation_cost_id)->update([
          'status_cost' => 2
        ]);
      }
      // SubmissionCost::find($sc->id)->delete();
      Journal::where('id',$sc->journal_posting_id)->delete();
      $sc->update([
        'status' => 2,
        'journal_posting_id' => null,
      ]);
      // Payable::where('id',$sc->payable_id)->delete();
      // CashTransaction::where('id',$sc->cash_transaction_id)->delete();
      DB::commit();
    }

    public function reject($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      if ($sc->type_submission==1) {
        // Job Order Cost
        JobOrderCost::find($sc->relation_cost_id)->update([
          'status' => 4
        ]);
      } elseif ($sc->type_submission==2) {
        // Manifest Cost
        ManifestCost::find($sc->relation_cost_id)->update([
          'status' => 4
        ]);
      } elseif ($sc->type_submission==4) {
        // Transaksi Kas
        CashTransaction::find($sc->relation_cost_id)->update([
          'status_cost' => 4
        ]);
      }
      $sc->update([
        'status' => 3
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function posting($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      $j=null;

      if ($sc->type_submission==1)
        $this->postingJobOrderCost($sc);
      else if ($sc->type_submission == 2)
        $this->postingPackingListCost($sc);
      elseif ($sc->type_submission == 4)
        $this->postingTransaksiKas($sc);
      
      DB::commit();

      return Response::json(null);
    }

    public function revisi($id)
    {
      DB::beginTransaction();
      $sc=SubmissionCost::find($id);
      if ($sc->type_submission==1) {
        // Job Order Cost
        JobOrderCost::find($sc->relation_cost_id)->update([
          'status' => 1
        ]);
      } elseif ($sc->type_submission==2) {
        // Manifest Cost
        ManifestCost::find($sc->relation_cost_id)->update([
          'status' => 1
        ]);
      } elseif ($sc->type_submission==4) {
        // Transaksi Kas
        CashTransaction::find($sc->relation_cost_id)->update([
          'status_cost' => 1
        ]);
      }
      $sc->delete();
      DB::commit();

      return Response::json(null);
    }

    private function postingJobOrderCost(SubmissionCost $sc)
    {
      $jobOrderCost = JobOrderCost::find($sc->relation_cost_id);
      $costType = CostType::find($jobOrderCost->cost_type_id);
      $ven = Contact::find($jobOrderCost->vendor_id);
      
      $jurnal = Journal::create([
        'company_id' => $sc->company_id,
        'type_transaction_id' => $sc->type_transaction_id,
        'date_transaction' => $jobOrderCost->header->shipment_date,
        'created_by' => auth()->id(),
        'code' => $jobOrderCost->header->code,
        'status' => 2,
        'description' => "Pengajuan Biaya Job Order - ".$jobOrderCost->header->code??'',
        'debet' => 0,
        'credit' => 0,
      ]);

      //jika accrual
      if ($costType->type==1)
        $akun = $costType->akun_uang_muka;
      else
        $akun = $costType->akun_biaya;

      JournalDetail::create([
        'header_id' => $jurnal->id,
        'account_id' => $akun,
        'debet' => $jobOrderCost->total_price,
        'credit' => 0,
      ]);

      JournalDetail::create([
        'header_id' => $jurnal->id,
        'account_id' => $costType->akun_kas_hutang,
        'cash_category_id' => $costType->cash_category_id,
        'debet' => 0,
        'credit' => $jobOrderCost->total_price,
      ]);

      // cek jika kas / hutang dari no_cash_bank
      $account=Account::find($costType->akun_kas_hutang);
      if (in_array($account->no_cash_bank,[1,2])) { //cek account kas = 1 atau bank = 2
        //simpan di transaksi kas
        $ct=CashTransaction::create([
          'company_id' => $sc->company_id,
          'type_transaction_id' => $sc->type_transaction_id,
          'code' => $jobOrderCost->header->code,
          'reff' => $jobOrderCost->header->code,
          'jenis' => 2,
          'type' => $account->no_cash_bank,
          'total' => $jobOrderCost->total_price,
          'account_id' => $account->id,
          'date_transaction' => $jobOrderCost->header->shipment_date,
          'status_cost' => 3,
          'is_cut' => 1,
          'created_by' => auth()->id(),
          'journal_id' => $jurnal->id,
        ]);

        CashTransactionDetail::create([
          'header_id' => $ct->id,
          'account_id' => $akun,
          'contact_id' => $jobOrderCost->vendor_id,
          'amount' => $jobOrderCost->total_price,
          'jenis' => 1
        ]);
        $cash_transaction_id=$ct->id;
      } else {
        $p=Payable::create([
          'company_id' => $sc->company_id,
          'contact_id' => $jobOrderCost->vendor_id,
          'type_transaction_id' => $sc->type_transaction_id,
          'created_by' => auth()->id(),
          'journal_id' => $j->id,
          'code' => $jobOrderCost->header->code,
          'date_transaction' => $jobOrderCost->header->shipment_date,
          'date_tempo' => $ven->term_of_payment??Carbon::now(),
          'debet' => 0,
          'credit' => $jobOrderCost->total_price,
        ]);
        $payd=PayableDetail::create([
          'header_id' => $p->id,
          'type_transaction_id' => $sc->type_transaction_id,
          'code' => $jobOrderCost->header->code,
          'date_transaction' => $jobOrderCost->header->shipment_date,
          'debet' => 0,
          'credit' => $jobOrderCost->total_price,
        ]);
        $payable_id=$p->id;
      }

      $sc->update([
        'status' => 4,
        'journal_posting_id' => $jurnal->id]
      );

      JobOrderCost::find($sc->relation_cost_id)->update([
        'status' => 5
      ]);
    }

    private function postingPackingListCost(SubmissionCost $sc)
    {
      $manifestCost = ManifestCost::find($sc->relation_cost_id);
      $costType = CostType::find($manifestCost->cost_type_id);
      $vendor = Contact::find($manifestCost->vendor_id);

      $jurnal = Journal::create([
        'company_id' => $sc->company_id,
        'type_transaction_id' => $sc->type_transaction_id,
        'date_transaction' => $manifestCost->header->date_manifest,
        'created_by' => auth()->id(),
        'code' => $manifestCost->header->code,
        'status' => 2,
        'description' => "Pengajuan Biaya Packing List - ".$manifestCost->header->code??'',
        'debet' => 0,
        'credit' => 0,
      ]);
      
      //jika accrual
      if ($costType->type==1)
        $akun = $costType->akun_uang_muka;
      else
        $akun = $costType->akun_biaya;

      JournalDetail::create([
        'header_id' => $jurnal->id,
        'account_id' => $akun,
        'debet' => $manifestCost->total_price,
        'credit' => 0,
      ]);

      JournalDetail::create([
        'header_id' => $jurnal->id,
        'account_id' => $costType->akun_kas_hutang,
        'cash_category_id' => $costType->cash_category_id,
        'debet' => 0,
        'credit' => $manifestCost->total_price,
      ]);
        
      // cek jika kas / hutang dari no_cash_bank
      $account = Account::find($costType->akun_kas_hutang);
      if (in_array($account->no_cash_bank,[1,2])) {
        //cek kas = 1 / bank = 2
        //simpan di transaksi kas
        $cashTransaction = CashTransaction::create([
          'company_id' => $sc->company_id,
          'type_transaction_id' => $sc->type_transaction_id,
          'code' => $manifestCost->header->code,
          'reff' => $manifestCost->header->code,
          'jenis' => 2,
          'type' => $account->no_cash_bank,
          'total' => $manifestCost->total_price,
          'account_id' => $costType->cash_category_id,
          'date_transaction' => $manifestCost->header->date_manifest,
          'status_cost' => 3,
          'created_by' => auth()->id(),
          'journal_id' => $jurnal->id,
        ]);

        CashTransactionDetail::create([
          'header_id' => $cashTransaction->id,
          'account_id' => $akun,
          'contact_id' => $manifestCost->vendor_id,
          'amount' => $manifestCost->total_price,
          'jenis' => 1
        ]);

        $sc->update([
          'cash_transaction_id' => $cashTransaction->id
        ]);
      } else {
        $payable = Payable::create([
          'company_id' => $sc->company_id,
          'contact_id' => $manifestCost->vendor_id,
          'type_transaction_id' => $sc->type_transaction_id,
          'created_by' => auth()->id(),
          'journal_id' => $jurnal->id,
          'code' => $manifestCost->header->code,
          'date_transaction' => $manifestCost->header->date_manifest,
          'date_tempo' => $vendor->term_of_payment??Carbon::now(),
          'debet' => 0,
          'credit' => $manifestCost->total_price,
        ]);

        $payableDetail = PayableDetail::create([
          'header_id' => $payable->id,
          'type_transaction_id' => $sc->type_transaction_id,
          'code' => $manifestCost->header->code,
          'date_transaction' => $manifestCost->header->date_manifest,
          'debet' => 0,
          'credit' => $manifestCost->total_price,
        ]);

        $sc->update([
          'payable_id' => $payable->id
        ]);
      }

      ManifestCost::find($sc->relation_cost_id)->update([
        'status' => 5
      ]);

      $sc->update([
        'status' => 4,
        'journal_posting_id' => $j->id
      ]);
    }

    private function postingPickupOrderCost()
    {}

    private function postingTransaksiKas(SubmissionCost $cost)
    {
      $transaksiKas = CashTransaction::find($cost->relation_cost_id);
      $detailTransaksiKas = CashTransactionDetail::where('header_id', $transaksiKas->id)->get();

      $jurnal = Journal::create([
        'company_id' => $cost->company_id,
        'relation_id' => $transaksiKas->id,
        'type_transaction_id' => $cost->type_transaction_id,
        'date_transaction' => $transaksiKas->date_transaction,
        'created_by' => auth()->id(),
        'code' => $transaksiKas->code,
        'status' => 2,
        'description' => "Pengajuan Biaya Transaksi Kas - ".$transaksiKas->code??'',
        'debet' => 0,
        'credit' => 0,
      ]);

      JournalDetail::create([
        'header_id' => $jurnal->id,
        'account_id' => $transaksiKas->account_id,
        'debet' => ($transaksiKas->jenis==1?$transaksiKas->total:0),
        'credit' => ($transaksiKas->jenis==2?$transaksiKas->total:0),
      ]);

      foreach ($detailTransaksiKas as $key => $value) {
        $journalDetail = JournalDetail::create([
          'header_id' => $jurnal->id,
          'account_id' => $value->account_id,
          'cash_category_id' => $value->cash_category_id,
          'debet' => ($transaksiKas->jenis==2?$value->amount:0),
          'credit' => ($transaksiKas->jenis==1?$value->amount:0),
        ]);
      }
      
      $transaksiKas->update([
        'status_cost' => 3, //selesai
        'journal_id' => $jurnal->id
      ]);

      $cost->update([
        'status' => 4,
        'journal_posting_id' => $jurnal->id
      ]);
    }
}
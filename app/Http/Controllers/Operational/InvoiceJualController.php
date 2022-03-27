<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\InvoiceTax;
use App\Model\Company;
use App\Model\Contact;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\JobOrderCost;
use App\Model\WorkOrder;
use App\Model\Account;
use App\Model\CostType;
use App\Model\Manifest;
use App\Model\Tax;
use App\Model\Journal;
use App\Model\AccountDefault;
use App\Model\JournalDetail;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
use App\Model\QuotationDetail;
use App\Model\PriceList;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Utils\TransactionCode;
use App\Model\Notification;
use App\Model\NotificationType;
use App\Model\NotificationTypeUser;
use App\Model\NotificationUser;
use App\Model\InvoiceJoin;
use Response;
use PDF;
use Carbon\Carbon;
use App\Abstracts\Operational\InvoiceDetail AS InvDetail;
use App\Abstracts\Finance\Account AS COA;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Sales\SalesOrder;
use App\Abstracts\JobOrderDetail AS JOD;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceJualController extends Controller
{
    /*
      Date : 24-03-2020
      Description : Mengambil remark
      Developer : Didin
      Status : Create
    */
    public function __construct() {
        $this->remark = DB::table('print_remarks')
        ->first();
    }

    public function index()
    {
        $data['customer_list']=DB::table('invoices')->leftJoin('contacts','contacts.id','invoices.customer_id')->groupBy('invoices.customer_id')->selectRaw('contacts.id,contacts.name,group_concat(invoices.id) as invoice_list')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
      $code = new TransactionCode(1, 'Handling');
        $code->setCode();
        $trx_code = $code->getCode();
      $data['account']=Account::with('parent')->whereRaw("is_base = 0")->select('id','code','name')->get();
      // $data['cost_type']=CostType::with('parent')->where('parent_id','!=',null)->where('is_invoice', 1)->get();
      $data['cost_type']=DB::table('cost_types')
      ->leftJoin('cost_types as parent','parent.id','=','cost_types.parent_id')
      ->leftJoin('companies','companies.id','=','cost_types.company_id')
      ->where('cost_types.parent_id','!=',null)
      ->where('cost_types.is_invoice', 1)
      ->select([
        'cost_types.id',
        DB::raw("CONCAT(cost_types.name,' - ',companies.name) as name"),
        'cost_types.qty',
        'cost_types.vendor_id',
        'cost_types.cost as price',
        'cost_types.initial_cost as total_price',
        DB::raw("concat(null) as job_order_id"),
        'parent.name as parent'
      ])->get();
      $data['tax']=Tax::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_job_order_costs(Request $request) {
      $data['cost_type']=DB::table('cost_types')
      ->leftJoin('cost_types as parent','parent.id','=','cost_types.parent_id')
      ->join('job_order_costs','cost_types.id','=','cost_type_id')
      ->where('job_order_costs.type',2)
      ->where('cost_types.parent_id','!=',null)
      ->where('header_id', $request->job_order_id)
      ->select([
        'cost_types.id',
        'cost_types.name',
        'job_order_costs.qty',
        'cost_types.vendor_id',
        'job_order_costs.price as price',
        'job_order_costs.total_price as total_price',
        DB::raw("concat(null) as job_order_id"),
        'parent.name as parent'
      ])->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 18-03-2020
      Description : Menambah invoice
      Developer : Didin
      Status : Edit
    */
    /*
      Date : 28-07-2021
      Description : Menyesuaikan penambahan invoice untuk Sales Order
      Developer : Hendra
      Status : Edit
    */
    public function store(Request $request)
    {
      $request->validate([
        'company_id' => 'required',
        'journal_date' => 'required',
        'customer_id' => 'required',
        'type_bayar' => 'required',
        // 'cash_account_id' => 'required_if:type_bayar,1',
        'termin' => 'required_if:type_bayar,2',
        'grand_total' => 'required|integer|min:1',
      ], [
        'company_id.required' => 'Company tidak boleh kosong',
        'journal_date.required' => 'Tanggal tidak boleh kosong',
        'customer_id.required' => 'Customer tidak boleh kosong',
        'type_bayar.required' => 'Tipe bayar tidak boleh kosong',
        // 'cash_account_id.required_if' => 'Cash Account tidak boleh kosong',
        'termin.required_if' => 'Termin tidak boleh kosong',
        'grand_total.required' => 'Grand Total tidak boleh kosong',
        'grand_total.integer' => 'Grand Total harus berupa angka',
        'grand_total.min:1' => 'Grand Total harus ebih dari 0',
      ]);
      
      $statusSO = collect($request->detail)->pluck('sales_order_status_id')->unique()
                        ->filter(function($x){
                          if($x != null){
                            return $x;
                          }
                        })->count();
      
      if($statusSO > 1){
        return response()->json(['message' => 'Detail Invoice harus memiliki status Sales Order yang sama'], 421);
      }

      $typeBayarSo = collect($request->detail)->first();
      if(isset($typeBayarSo['payment_type']) && $typeBayarSo['payment_type'] == 1){
        $request->merge(['type_bayar' => 1]);
      }

      if ($request->type_bayar==1) {
        $defaultAccountCash = AccountDefault::first();
        if(!$defaultAccountCash->account_cash){
          return response()->json(['message' => 'Default Account Cash belum diatur. Silahkan lakukan atur pada Setting / Keuangan / Setting Akun'], 421);
        }
      }
      
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'invoice');
      $code->setCode();
      $trx_code = $code->getCode();

      $request->account_receivable_id = COA::getReceivable();

      $i=Invoice::create([
        'company_id' => $request->company_id,
        'customer_id' => $request->customer_id,
        'account_cash_id' => $request->type_bayar == 1 ? ($request->cash_account_id ?? $defaultAccountCash->account_cash) : null,
        'type_transaction_id' => 26,
        'description' => $request->description,
        'account_receivable_id' => $request->account_receivable_id,
        'termin' => $request->termin,
        'due_date' => ($request->type_bayar==2?(Carbon::parse($request->date_invoice)->addDays($request->termin)):null),
        'type_bayar' => $request->type_bayar,
        'create_by' => auth()->id(),
        'code' => $trx_code,
        'date_invoice' => dateDB($request->date_invoice),
        'journal_date' => dateDB($request->journal_date),
        'status' => 2,
        'sub_total' => round($request->sub_total),
        // 'discount_percent' => $request->discount_percent,
        'discount_total' => round($request->discount_total),
        'is_ppn' => $request->is_ppn,
        'total_another_ppn' => round($request->total_another_ppn),
        'grand_total' => round($request->grand_total),
        // 'sub_total_additional' => $request->sub_total_additional,
        // 'discount_percent_additional' => $request->discount_percent_additional,
        // 'discount_total_additional' => $request->discount_total_additional,
        // 'grand_total_additional' => $request->grand_total_additional,
      ]);
      $job_order_details = [];
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $id=InvoiceDetail::create([
          'header_id' => $i->id,
          'job_order_id' => $value['job_order_id'] ?? null,
          'job_order_detail_id' => $value['job_order_detail_id'] ?? null,
          'work_order_id' => $value['work_order_id'] ?? null,
          'cost_type_id' => $value['cost_type_id'] ?? null,
          'price' => $value['price'],
          'total_price' => $value['total_price'],
          'imposition' => $value['imposition']??1,
          'qty' => $value['qty'],
          'description' => $value['description']??'-',
          'is_other_cost' => $value['is_other_cost'] ?? 0,
          'type_other_cost' => $value['type_other_cost'] ?? 0,
          'manifest_id' => $value['manifest_id'] ?? null,
          'create_by' => auth()->id(),
          'imposition_name' => $value['imposition_name']??'-',
          'commodity_name' => $value['commodity_name']??'-',
          'discount' => round($value['discount']),
          'is_ppn' => $value['is_ppn'] ?? 0,
          'ppn' => round($value['ppn']),
        ]);

        if(null == ($value['cost_type_id'] ?? null)) {
            if(null != ($value['job_order_detail_id'] ?? null)) {
              $job_order_detail = JobOrderDetail::find($value['job_order_detail_id']);
              if($job_order_detail->job_order->service_type_id == 1 || $job_order_detail->job_order->service_type_id == 2 || $job_order_detail->job_order->service_type_id == 3) {
                  array_push($job_order_details, $job_order_detail);
              }
            } else  {
              $job_order = JobOrder::find($value['job_order_id']);
              if($job_order->service_type_id == 1 || $job_order->service_type_id == 2 || $job_order->service_type_id == 3 || $job_order->service_type_id == 4) {
                  $jod = JobOrderDetail::join('manifest_details AS MD', 'MD.job_order_detail_id', 'job_order_details.id')
                  ->join('manifests AS M', 'M.id', 'MD.header_id')
                  ->join('containers AS C', 'C.id', 'M.container_id')
                  ->where('job_order_details.header_id', $job_order->id)
                  ->select('job_order_details.id')
                  ->get();
                  foreach ($jod as $unit) {
                      $job_order_detail = JobOrderDetail::find($unit->id);
                      array_push($job_order_details, $job_order_detail);
                  }
              }
            }
        }


        foreach ($value['detail_tax'] as $vws) {
          InvoiceTax::create([
            'header_id' => $i->id,
            'invoice_detail_id' => $id->id,
            'tax_id' => $vws['tax_id'],
            'amount' => $vws['value']
          ]);
        }

        if (($value['job_order_id'] ?? null) && !($value['cost_type_id'] ?? null)) {
          JobOrder::find($value['job_order_id'])->update([
            'invoice_id' => $i->id,
            'code_invoice' => $trx_code,
            'date_invoice' => dateDB($request->date_invoice),
            'status' => 3
          ]);
          $jo=JobOrder::find($value['job_order_id']);
          if($jo->work_order_id) {
              $cekwo=DB::table('job_orders')->whereRaw("work_order_id = $jo->work_order_id and invoice_id is null")->count();
              if ($cekwo<1) {
                WorkOrder::find($jo->work_order_id)->update([
                  'is_invoice' => 1
                ]);
              }
          }
        }
        if (isset( $value['manifest_id'] )) {
          Manifest::find( $value['manifest_id'] )->update([
            'is_invoice' => 1
          ]);
        }
        if (isset($value['work_order_id'])) {
          JobOrder::where('work_order_id', $value['work_order_id'])->where('invoice_id',null)->update([
            'invoice_id' => $i->id,
            'code_invoice' => $trx_code,
            'date_invoice' => dateDB($request->date_invoice),
            'status' => 3
          ]);

          WorkOrder::find($value['work_order_id'])->update([
            'is_invoice' => 1
          ]);
        }

      }


      $is_scheduled = 0;
      $prev_date = null;
      $current_date = null;
      foreach ($job_order_details as $key => $detail) {
          if($detail->manifest_detail) {
            $manifest = $detail->manifest_detail->manifest;

            if($manifest->is_container == 1) {
                if($manifest->container) {
                    $voyage_schedule = $manifest->container->voyage_schedule;
                    if($voyage_schedule->departure != null) {
                        ++$is_scheduled;
                        $current_date = Carbon::parse($voyage_schedule->departure);
                    }
                }
            } else if($manifest->is_container == 0) {
                if($manifest->depart != null) {
                        ++$is_scheduled;
                        $current_date = Carbon::parse($manifest->depart);
                }
            }
            if($current_date != null) {
                if($key == 0) {
                    $prev_date = $current_date;
                } else {
                    if($current_date->gt($prev_date)) {
                        $prev_date = $current_date;
                    }
                }

               $i->update([
                    'date_invoice' => $prev_date->format('Y-m-d')
                ]);
            }

          }
      }
      if(count($job_order_details) == 0 && !$request->filled('date_invoice')) {

          return Response::json(['message' => 'Tanggal wajib diisi'], 500);
      }

      if ($request->type_bayar==1) {
        // JIKA BAYAR KAS

      }
      // Notifikasi
      $slug=str_random(6);
      $customer=Contact::find($request->customer_id);
      $userList=DB::table('notification_type_users')
      ->leftJoin('users','users.id','=','notification_type_users.user_id')
      ->whereRaw("notification_type_users.notification_type_id = 14")
      ->select('users.id','users.is_admin','users.company_id')->get();
      $n=Notification::create([
        'notification_type_id' => 14,
        'name' => 'Invoice Jual Baru telah Dibuat!',
        'description' => 'No. Invoice Jual '.$trx_code.' nama customer '.$customer->name,
        'slug' => $slug,
        'route' => 'operational.invoice_jual.show',
        'parameter' => json_encode(['id' => $i->id])
      ]);
      foreach ($userList as $un) {
        if ($un->company_id==$request->company_id) {
          NotificationUser::create([
            'notification_id' => $n->id,
            'user_id' => $un->id
          ]);
        }
      }
      $this->storeInvoiceInWorkOrder();
      InvDetail::setServiceId($i->id);
      DB::commit();

      return Response::json(null);
    }

    /*
      Date : 09-03-2020
      Description : Menampilkan detail invoice jual
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
        $data['item']=Invoice::with('company','customer', 'journal')
        ->leftJoin('tax_invoices', 'tax_invoices.invoice_id', 'invoices.id')
        ->where('invoices.id', $id)
        ->selectRaw('invoices.*, tax_invoices.code AS tax_invoice_code')
        ->first();

        $data['taxes']=InvoiceTax::where('header_id', $id)->sum('amount');
        $data['addon']=DB::select("select group_concat(distinct job_orders.aju_number SEPARATOR ', ') as aju,group_concat(distinct job_orders.no_bl SEPARATOR ', ') as bl, GROUP_CONCAT(distinct work_orders.code SEPARATOR ', ') as code_wo from invoice_details left join job_orders on job_orders.id = invoice_details.job_order_id
        left join work_orders on work_orders.id = job_orders.work_order_id where invoice_details.header_id = $id")[0];
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function showDetail($id)
    {
        $taxcount = DB::table('taxes')
        ->count('id');
        $detail1 = InvoiceDetail::with('job_order','manifest.container','manifest.vehicle','job_order.commodity:id,name','job_order.service:id,name,service_type_id','job_order.trayek','cost_type:id,name,description', 'job_order.sales_order.customer_order:id,payment_type')
        ->where('invoice_details.header_id', $id)
        ->get();
        // dd($detail1->first());
        foreach($detail1 as $detail) {
            $taxes = [];
            $invoice_taxes = DB::table('invoice_taxes')
            ->whereNotNull('tax_id')
            ->whereInvoiceDetailId($detail->id)
            ->get();

            if($detail->job_order_id) {
                if($detail->job_order->quotation_id != null) {
                    $quotation = DB::table('quotations')
                    ->whereId($detail->job_order->quotation_id)
                    ->select('bill_type')
                    ->first();

                    if(($quotation->bill_type ?? null) == 2) {
                        $work_order = DB::table('work_orders')
                        ->whereId($detail->job_order->work_order_id)
                        ->select('name')
                        ->first();

                        $detail->job_order->service->name = $work_order->name;
                    }
                }
            }

            foreach($invoice_taxes as $tax) {
                array_push($taxes, [
                    'id' => $tax->id,
                    'tax_id' => $tax->tax_id,
                    'value' => $tax->amount
                ]);
            }

            for($i = count($taxes) - 1; $i < $taxcount; $i++) {
                array_push($taxes, [
                    'tax_id' => null,
                    'value' => 0
                ]);
            }

            $detail->detail_tax = $taxes;
        }
        return response()->json($detail1, 200, [], JSON_NUMERIC_CHECK);
    }

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
        'company_id' => 'required',
        'journal_date' => 'required',
        'customer_id' => 'required',
        'type_bayar' => 'required',
        // 'cash_account_id' => 'required_if:type_bayar,1',
        'termin' => 'required_if:type_bayar,2',
        'grand_total' => 'required|integer|min:1',
      ], [
        'company_id.required' => 'Company tidak boleh kosong',
        'journal_date.required' => 'Tanggal tidak boleh kosong',
        'customer_id.required' => 'Customer tidak boleh kosong',
        'type_bayar.required' => 'Tipe bayar tidak boleh kosong',
        // 'cash_account_id.required_if' => 'Cash Account tidak boleh kosong',
        'termin.required_if' => 'Termin tidak boleh kosong',
        'grand_total.required' => 'Grand Total tidak boleh kosong',
        'grand_total.integer' => 'Grand Total harus berupa angka',
        'grand_total.min:1' => 'Grand Total harus ebih dari 0',
      ]);

      $statusSO = collect($request->detail)->pluck('sales_order_status_id')->unique()
                        ->filter(function($x){
                          if($x != null){
                            return $x;
                          }
                        })->count();

      if($statusSO > 1){
        return response()->json(['message' => 'Detail Invoice harus memiliki status Sales Order yang sama'], 422);
      }

      $typeBayarSo = collect($request->detail)->first();
      if(isset($typeBayarSo['payment_type']) && $typeBayarSo['payment_type'] == 1){
        $request->merge(['type_bayar' => 1]);
      }

      if ($request->type_bayar==1) {
        $defaultAccountCash = AccountDefault::first();
        if(!$defaultAccountCash->account_cash){
          return response()->json(['message' => 'Default Account Cash belum diatur. Silahkan lakukan atur pada Setting / Keuangan / Setting Akun'], 422);
        }
      }
        DB::beginTransaction();
        $i=Invoice::find($id);
        $invoiceCode = $i->code;
        $i->update([
            'company_id' => $request->company_id,
            'customer_id' => $request->customer_id,
            'account_cash_id' => $request->type_bayar == 1 ? ($request->cash_account_id ?? $defaultAccountCash->account_cash) : null,
            'description' => $request->description,
            'account_receivable_id' => $request->account_receivable_id,
            'termin' => $request->termin,
            'due_date' => ($request->type_bayar==2?(Carbon::parse($request->date_invoice)->addDays($request->termin)):null),
            'type_bayar' => $request->type_bayar,
            'create_by' => auth()->id(),
            'date_invoice' => dateDB($request->date_invoice),
            'journal_date' => dateDB($request->journal_date),
            'sub_total' => round($request->sub_total),
            // 'discount_percent' => $request->discount_percent,
            'discount_total' => round($request->discount_total),
            'is_ppn' => $request->is_ppn,
            'total_another_ppn' => round($request->total_another_ppn),
            'grand_total' => round($request->grand_total),
        ]);
        $i->increment('qty_edit', 1);

        $listJo = collect($request->detail)->where('cost_type_id', null)->pluck('job_order_id')->toArray();
        $ivd=DB::table('invoice_details')->where('header_id', $id)->where('cost_type_id', null)->select('job_order_id')->get();
        // return response()->json($ivd,500);

        InvoiceTax::whereHeaderId($id)->delete();
        DB::table('invoice_details')->whereHeaderId($id)->delete();

        foreach ($request->detail as $key => $value) {
          if (empty($value)) {
            continue;
          }
          $invoice_detail=InvoiceDetail::create([
            'header_id' => $id,
            'job_order_id' => $value['job_order_id'] ?? null,
            'job_order_detail_id' => $value['job_order_detail_id'],
            'work_order_id' => $value['work_order_id'],
            'cost_type_id' => $value['cost_type_id'],
            'price' => $value['price'],
            'total_price' => $value['total_price'],
            'imposition' => $value['imposition']??1,
            'qty' => $value['qty'],
            'description' => $value['description']??'-',
            'is_other_cost' => $value['is_other_cost'],
            'is_other_cost' => $value['is_other_cost'],
            'type_other_cost' => $value['type_other_cost'],
            'manifest_id' => $value['manifest_id']??null,
            'create_by' => auth()->id(),
            'imposition_name' => $value['imposition_name']??'-',
            'commodity_name' => $value['commodity_name']??'-',
            'discount' => round($value['discount']),
            'is_ppn' => $value['is_ppn']??0,
            'ppn' => round($value['ppn']),
          ]);

          foreach ($value['detail_tax'] as $vws) {
            InvoiceTax::create([
              'header_id' => $id,
              'invoice_detail_id' => $invoice_detail->id,
              'tax_id' => $vws['tax_id'],
              'amount' => $vws['value']
            ]);
          }

          if (isset($value['job_order_id'])) {
            JobOrder::find($value['job_order_id'])->update([
              'invoice_id' => $id,
              'code_invoice' => $i->first()->code,
              'date_invoice' => dateDB($request->date_invoice),
              'status' => 3
            ]);
            $jo=JobOrder::find($value['job_order_id']);
            $cekwo=DB::table('job_orders')->whereRaw("work_order_id = $jo->work_order_id and invoice_id is null")->count();
            if ($cekwo<1) {
              WorkOrder::find($jo->work_order_id)->update([
                'is_invoice' => 1
              ]);
            }
          }
          if (isset( $value['manifest_id'] )) {
            Manifest::find( $value['manifest_id'] )->update([
              'is_invoice' => 1
            ]);
          }
          if (isset($value['work_order_id'])) {
            JobOrder::where('work_order_id', $value['work_order_id'])->where('invoice_id',null)->update([
              'invoice_id' => $id,
              'code_invoice' => $invoiceCode,
              'date_invoice' => dateDB($request->date_invoice),
              'status' => 3
            ]);

            WorkOrder::find($value['work_order_id'])->update([
              'is_invoice' => 1
            ]);
          }

        }
        $this->storeInvoiceInWorkOrder();
        InvDetail::setServiceId($id);

        foreach ($ivd as $v) {
          if (!in_array($v->job_order_id,$listJo)) {
            DB::table('job_orders')->where('id', $v->job_order_id)->update([
              'invoice_id' => null,
              'status' => 2,
              'code_invoice' => null,
              'date_invoice' => null
            ]);
          }
        }
        DB::commit();

        return Response::json(['message' => 'Data berhasil diupdate']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      $dataManifest=InvoiceDetail::where('header_id', $id)->get();
      foreach ($dataManifest as $key => $value) {
        $work_order_id = $value->work_order_id;
        if($work_order_id != null) {
          WorkOrder::find( $work_order_id )->update([
            'is_invoice' => 0
          ]);
        }
        Manifest::where('id','=',$value->manifest_id)->update([
          'is_invoice' => 0
        ]);
      }

      JobOrder::where('invoice_id','=',$id)->update([
        'invoice_id' => null,
        'code_invoice' => null,
        'date_invoice' => null,
        'status' => 1
      ]);





      DB::beginTransaction();
      $this->storeInvoiceInWorkOrder();
      Invoice::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function cari_customer_list($company_id)
    {
      $condition = $company_id != 0 ? "company_id = $company_id" : '1=1';
      $data['item']=Contact::where('is_pelanggan', 1)->whereRaw($condition)->select('id','name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_jo($id)
    {
      $jo=JobOrder::with('commodity','trayek')->where('id', $id)->first();
      if (in_array($jo->service_type_id,[2,3])) {
        if ($jo->service_type_id==3) {
          $imposition='UNIT';
        } elseif ($jo->service_type_id==2) {
          $imposition='KONTAINER';
        }
        $sql="
        SELECT
          manifests.id,
          manifest_details.job_order_detail_id,
          manifests.code,
          contacts.name as driver,
          vehicles.nopol,
          containers.container_no,
          vehicle_types.name as vname,
          services.account_sale_id,
          services.name as service
        FROM
          manifests
          LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
          LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
          LEFT JOIN containers ON manifests.container_id = containers.id
          LEFT JOIN contacts ON contacts.id = manifests.driver_id
          LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
          LEFT JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id
          LEFT JOIN job_orders ON job_orders.id = job_order_details.header_id
          LEFT JOIN services ON services.id = job_orders.service_id
        WHERE
          manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $id )
        GROUP BY
          manifests.id
        ";
        $man=DB::select($sql);
      } else if ($jo->service_type_id==1) {
        $sql2="select
        concat(null) as manifest_id,
        concat(null) as job_order_detail_id,
        concat(null) as manifest_detail_id,
        concat(null) as driver,
        concat(null) as nopol,
        concat(null) as container_no,
        vehicle_types.name as vname,
        routes.name as trayek,
        jo.no_po_customer,
        jo.id as jo_id,
        jo.code as codes,
        jo.work_order_id as wo_id,
        (select group_concat(distinct item_name) from job_order_details as jod1 where jod1.header_id = jo.id) as item_name,
        Y.qty,
        Y.price,
        Y.total_price,
        Y.imposition,
        jo.description,
        services.name as service
        from job_orders as jo
        left join services on jo.service_id = services.id
        left join routes on jo.route_id = routes.id
        left join vehicle_types on jo.vehicle_type_id = vehicle_types.id
        left join (select if(imposition=1,volume,if(imposition=2,IF(weight > volumetric_weight, weight, volumetric_weight),qty)) as qty,header_id,imposition,price,total_price from job_order_details) Y on Y.header_id = jo.id
        where jo.id = $id and jo.invoice_id is null and jo.service_type_id = 1";

        $man=DB::select($sql2);
        // dd($man);
      } elseif (in_array($jo->service_type_id,[6,7])) {

        $man=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
                            ->leftJoin('services','services.id','=','job_orders.service_id')
                            ->leftJoin('pieces','pieces.id','=','job_orders.piece_id')
                            ->where("job_order_details.header_id", $id)
                            ->select(
                              'job_orders.code','job_orders.no_po_customer',
                              'job_orders.id',
                              'job_orders.description',
                              'job_orders.total_price',
                              'job_orders.price',
                              'job_order_details.id as job_order_detail_id',
                              'job_order_details.qty',
                              'job_order_details.item_name',
                              'pieces.name as piece_name',
                              'services.name as service'
                              )
                            ->first();
      } elseif(in_array($jo->service_type_id,[12, 13, 14, 15])) {
        $jo=JobOrder::with('service')->leftJoin('services', 'services.id', 'service_id')->where('job_orders.id', $id)->selectRaw('job_orders.*, services.service_type_id AS service_type_id')->first();
        if($jo->service_type_id == 14) {
          $data['service'] = DB::table('packagings')->where('job_order_id', $id)->first();
        }
        else if($jo->service_type_id == 15) {
          $data['service'] = DB::table('warehouserents')->where('job_order_id', $id)->selectRaw('warehouserents.*, DATEDIFF(end_date, start_date) AS durasi')->first();


        }
        else if($jo->service_type_id == 12) {
          $data['service'] = DB::table('handlings')->where('job_order_id', $id)->first();

        }
        else if($jo->service_type_id == 13) {
          $data['service'] = DB::table('stuffings')->where('job_order_id', $id)->first();

        }
        if(in_array($jo->service_type_id,[1,2,3,4,5,6,7])) {
            $man=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
            ->leftJoin('services','services.id','=','job_orders.service_id')
            ->leftJoin('pieces','pieces.id','=','job_orders.piece_id')
            ->where("job_order_details.header_id", $id)
            ->select(
              'job_orders.code','job_orders.no_po_customer',
              'job_order_details.id',
              'job_orders.description',
              'job_order_details.total_price',
              'job_order_details.price',
              'job_order_details.id as job_order_detail_id',
              'job_order_details.qty',
              'job_order_details.item_name',
              'job_order_details.imposition',
              'pieces.name as piece_name',
              'services.name as service'
              )
            ->get();
        } else {
            $existingBorongan = JobOrderDetail::whereHeaderId($id)
            ->whereImposition(4)
            ->count('id');
            if($existingBorongan < 1) {
                $man=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
                ->leftJoin('services','services.id','=','job_orders.service_id')
                ->leftJoin('pieces','pieces.id','=','job_orders.piece_id')
                ->where("job_order_details.header_id", $id)
                ->select(
                  'job_orders.code','job_orders.no_po_customer',
                  'job_order_details.id',
                  'job_orders.description',
                  'job_order_details.total_price',
                  'job_order_details.price',
                  'job_order_details.id as job_order_detail_id',
                  DB::raw('IF(job_order_details.imposition = 1, job_order_details.volume, IF(job_order_details.imposition = 2, job_order_details.weight, job_order_details.qty)) AS qty'),
                  'job_order_details.item_name',
                  'job_order_details.imposition',
                  'pieces.name as piece_name',
                  'services.name as service'
                  )
                ->get();
            } else {
                $man=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
                ->leftJoin('services','services.id','=','job_orders.service_id')
                ->leftJoin('pieces','pieces.id','=','job_orders.piece_id')
                ->where("job_order_details.header_id", $id)
                ->select(
                  'job_orders.code','job_orders.no_po_customer',
                  'job_order_details.id',
                  'job_orders.description',
                  DB::raw('SUM(job_order_details.total_price) AS total_price'),
                  DB::raw('SUM(job_order_details.price) AS price'),
                  'job_order_details.id as job_order_detail_id',
                  DB::raw('1 AS qty'),
                  DB::raw('GROUP_CONCAT(job_order_details.item_name) AS item_name'),
                  DB::raw('4 AS imposition'),
                  'pieces.name as piece_name',
                  'services.name as service'
                  )
                ->get();
            }
        }
      } else {
        return Response::json(['message' => 'Error Has Found!'],500);
      }
      $data['jo']=$jo;
      $data['manifest']=$man;
      $data['imposition']=$imposition??'-';
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_wo(Request $request, $id)
    {
      $wo=WorkOrder::find($id);
      $data=[];
      if($wo->is_job_packet == 0) {
          if (($wo->quotation->bill_type??null)==2) {
            // jika quotation borongan
            $stta=$wo->quotation;
            $jos=DB::table('job_orders')->where('work_order_id', $wo->id)->first();
            $data[]=[
              'job_order_id' => $jos->id,
              'job_order_detail_id' => null,
              'no_po_customer' => '-',
              'trayek' => '-',
              'commodity' => $wo->name,
              'cost_type_id' => null,
              'work_order_id' => $id,
              'price' => $stta->price_full_contract,
              'total_price' => ($wo->qty*$stta->price_full_contract),
              'imposition' => 1,
              'qty' => $wo->qty,
              'description' => 'Borongan',
              'is_other_cost' => 0,
              'type_other_cost' => 0,
              'manifest_id' => null,
              'imposition_name' => $stta->imposition_name,
              'vehicle_type_name' => '-',
              'code' => $wo->code,
              'driver' => '-',
              'nopol' => '-',
              'container_no' => '-',
            ];
            return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
          }
          $wr="1=1";
          if ($request->jo_list_append) {
            $wr.=" AND job_orders.id NOT IN ($request->jo_list_append)";
          }
          $jor=JobOrder::whereRaw("work_order_id = $id and invoice_id is null and service_type_id != 4 and $wr")->get();
          foreach ($jor as $key => $jo) {
            if (in_array($jo->service_type_id,[2,3])) {
              if ($jo->service_type_id==3) {
                $imposition='UNIT';
              } elseif ($jo->service_type_id==2) {
                $imposition='KONTAINER';
              }
              $sql="
              SELECT
                manifests.id,
                manifest_details.job_order_detail_id,
                job_order_details.header_id as job_order_id,
                manifests.code,
                contacts.name as driver,
                vehicles.nopol,
                vehicle_types.name as vname,
                services.name as service
              FROM
                manifests
                LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
                LEFT JOIN job_order_details ON manifest_details.job_order_detail_id = job_order_details.id
                LEFT JOIN job_orders ON job_orders.id = job_order_details.header_id
                LEFT JOIN services ON services.id = job_orders.service_id
                LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
                LEFT JOIN contacts ON contacts.id = manifests.driver_id
                LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
              WHERE
                manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $jo->id )
                AND job_orders.service_type_id IN (2,3)
              GROUP BY
                manifests.id
              ";
              $man=DB::select($sql);
              // dd($man);
              foreach ($man as $mas) {
                $data[]=[
                  'job_order_id' => $jo->id,
                  'job_order_detail_id' => $mas->job_order_detail_id,
                  'no_po_customer' => $jo->no_po_customer,
                  'trayek' => $jo->trayek->name??'-',
                  'commodity' => $mas->service,
                  'cost_type_id' => null,
                  'work_order_id' => $id,
                  'price' => $jo->price,
                  'total_price' => (in_array($jo->service_type_id,[2,3])?($jo->price):$jo->total_price),
                  'imposition' => 1,
                  'qty' => 1,
                  'description' => $jo->description??'-',
                  'is_other_cost' => 0,
                  'type_other_cost' => 0,
                  'manifest_id' => $mas->id,
                  'imposition_name' => ($jo->service_type_id==2?'Kontainer':'Unit'),
                  'vehicle_type_name' => $mas->vname,
                  'code' => $mas->code,
                  'driver' => $mas->driver??'-',
                  'nopol' => $mas->nopol??'-',
                  'container_no' => '-',
                ];
              }
            } else if ($jo->service_type_id==1 || $jo->service_type_id==12 || $jo->service_type_id==13 || $jo->service_type_id==15) {
              // $detail=JobOrderDetail::where('header_id', $jo->id)->get();
              $stt=[
                1 => 'Kubikasi',
                2 => 'Tonase',
                3 => 'Item',
              ];

              $sql2="
              select
              routes.name as trayek,
              jo.no_po_customer,
              jo.id as jo_id,
              jo.code as code_jo,
              jo.work_order_id as wo_id,
              (select group_concat(distinct item_name) from job_order_details as jod1 where jod1.header_id = jo.id) as item_name,
              Y.qty,
              Y.price,
              Y.total_price,
              Y.imposition,
              jo.description,
              services.name as service
              from job_orders as jo
              left join services on services.id = jo.service_id
              left join routes on jo.route_id = routes.id
              left join (select sum(if(imposition=1,volume,if(imposition=2,weight,qty))) as qty,header_id,imposition,sum(total_price) as total_price,max(price) as price from job_order_details group by header_id) Y on Y.header_id = jo.id
              where jo.id = {$jo->id} and jo.invoice_id is null and (jo.service_type_id = 1 || jo.service_type_id = 12 || jo.service_type_id = 13 || jo.service_type_id = 15)";
              $man=DB::select($sql2);
              // dd($man);
              foreach ($man as $mas) {
                $data[]=[
                  'job_order_id' => $mas->jo_id,
                  'no_po_customer' => $mas->no_po_customer,
                  'job_order_detail_id' => null,
                  'trayek' => $mas->trayek,
                  'commodity' => $mas->service,
                  'cost_type_id' => null,
                  'work_order_id' => $id,
                  'price' => $mas->price,
                  'total_price' => $mas->total_price,
                  'imposition' => $mas->imposition,
                  'imposition_name' => $stt[$mas->imposition],
                  'qty' => $mas->qty,
                  'description' => $mas->description??'-',
                  'is_other_cost' => 0,
                  'type_other_cost' => 0,
                  'manifest_id' => null,
                  'vehicle_type_name' => null,
                  'code' => $mas->code_jo,
                  'driver' => '-',
                  'nopol' => '-',
                  'container_no' => '-',
                ];
              }
              // return Response::json($data,500,[],JSON_NUMERIC_CHECK);
            } elseif (in_array($jo->service_type_id,[6,7])) {
              $mas=DB::table('job_order_details')
                  ->leftJoin('job_orders','job_orders.id','job_order_details.header_id')
                  ->leftJoin('services','services.id','job_orders.service_id')
                  ->leftJoin('pieces','pieces.id','job_orders.piece_id')
                  ->where("job_orders.id", $jo->id)
                  // ->whereIn('job_orders.service_type_id',[6,7])
                  ->selectRaw('
                    job_order_details.total_price,
                    job_order_details.price,
                    job_order_details.id as job_order_detail_id,
                    job_order_details.qty,
                    job_order_details.item_name,
                    job_orders.code,
                    job_orders.no_po_customer,
                    job_orders.id,
                    job_orders.description,
                    pieces.name as piece_name,
                    services.name as service
                  ')
                  ->first();
                  // dd($man);
              $data[]=[
                'job_order_id' => $mas->id,
                'job_order_detail_id' => $mas->job_order_detail_id,
                'no_po_customer' => $mas->no_po_customer,
                'trayek' => $mas->item_name,
                'commodity' => $mas->service,
                'cost_type_id' => null,
                'work_order_id' => $id,
                'price' => $mas->price,
                'total_price' => $mas->total_price,
                'imposition' => 1,
                'imposition_name' => $mas->piece_name,
                'qty' => $mas->qty,
                'description' => $mas->description??'-',
                'is_other_cost' => 0,
                'type_other_cost' => 0,
                'manifest_id' => null,
                'vehicle_type_name' => '-',
                'code' => $mas->code,
                'driver' => '-',
                'nopol' => '-',
                'container_no' => '-',
              ];
            } else {
              continue;
            }

          }
      } else {
         $jobOrder = DB::table('job_orders')
         ->whereWorkOrderId($wo->id)
         ->first();
         $grandtotal = DB::table('job_packets')
         ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
         ->where('work_order_details.header_id', $wo->id)
         ->sum('total_price');
         $jobPackets = DB::table('job_packets')
        ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
        ->where('work_order_details.header_id', $id)
        ->select('job_packets.id', 'job_packets.work_order_detail_id', 'work_order_details.price_list_id', 'work_order_details.quotation_detail_id', 'job_packets.qty', 'job_packets.duration', 'job_packets.price', 'job_packets.total_price')
        ->orderBy('job_packets.total_price', 'desc')
        ->get();
        $data = [];
        foreach ($jobPackets as $unit) {
             if($unit->price_list_id) {
                $priceList = DB::table('price_lists')
                ->whereId($unit->price_list_id)
                ->select('service_id', 'handling_type')
                ->first();
                $service_id = $priceList->service_id;
             } else {
                $quotationDetail = DB::table('quotation_details')
                ->whereId($unit->quotation_detail_id)
                ->select('service_id', 'handling_type')
                ->first();
                $service_id = $quotationDetail->service_id;
             }
             $service = DB::table('services')
             ->whereId($service_id)
             ->first();
             $service_name = $service->name;

            if($unit->total_price > 0) {
                $qty = $unit->qty * $unit->duration;
                if($qty == 0) {
                    $qty = 1;
                }
                $param =  [
                    'work_order_id' => $id,
                    'job_order_id' => $jobOrder->id,
                    'commodity' => $service_name,
                    'is_ppn' => $service->is_ppn,
                    'job_order_detail_id' => null,
                    'imposition_name' => 'Paket',
                    'qty' => $qty,
                    'price' => $unit->price,
                    'total_price' => $unit->total_price,
                    'trayek' => null,
                    'code' => $wo->code,
                    'no_po_customer' => null,
                    'container_no' => null,
                    'cost_type_id' => null,
                    'is_other_cost' => 0,
                    'type_other_cost' => 0,
                    'manifest_id' => null,
                    'driver' => null
                ];
                if($service->is_ppn) {
                    $data[] = $param;
                } else {
                    array_unshift($data, $param);
                }
            }

        }

      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_default_akun()
    {
      $data=AccountDefault::first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_wo_collectible($id)
    {
      // $sql="SELECT work_orders.id FROM work_orders LEFT JOIN job_orders on job_orders.work_order_id = work_orders.id"
    }

    public function cancel_posting($id)
    {
      DB::beginTransaction();
      $i=Invoice::find($id);
      $i->increment('qty_batal_posting', 1);
      $journal_id=$i->journal_id;
      CashTransaction::where('type_transaction_id', 26)->where('relation_id', $i->id)->delete();
      Receivable::where('type_transaction_id', 26)->where('relation_id', $i->id)->delete();
      $i->update([
        'journal_id' => null,
        'status' =>2
      ]);
      Journal::where('id', $journal_id)->delete();

      DB::commit();
      return Response::json(null,200);
    }

    /*
      Date : 09-03-2020
      Description : Mem-posting invoice jual ke jurnal dan memberikan
                    faktur pajak kepada invoice
      Developer : Didin
      Status : Edit
    */
    public function posting(Request $request, $id)
    {
      $request->validate([
        'journal_date' => 'required'
      ], [
        'Tanggal posting Jurnal tidak boleh kosong'
      ]);


      DB::beginTransaction();
      $account_defaults = DB::table('account_defaults')
      ->first();
      if(($account_defaults->piutang ?? null) === null) {
          return Response::json(['message' => 'Akun piutang pada setting default akun masih belum di-setting. Silahkan lakukan setting akun piutang pada Setting / Keuangan / Setting Akun'], 421);
      }
      $ppn_count = DB::table('invoice_details')
      ->whereHeaderId($id)
      ->sum('ppn');
      if( $ppn_count > 0 ) {
          $tax_invoice_count = DB::table('tax_invoices')
            ->whereNull('invoice_id')
            ->whereRaw('DATE_FORMAT(NOW(), "%Y-%m-%d") >= start_date AND DATE_FORMAT(NOW(), "%Y-%m-%d") <= expiry_date')
            ->count('id');
          if($tax_invoice_count == 0) {
              return Response::json(['message' => 'Belum ada faktur pajak yang tersedia'], 421);
          } else {
            $latest_tax = DB::table('tax_invoices')
            ->whereNull('invoice_id')
            ->whereRaw('DATE_FORMAT(NOW(), "%Y-%m-%d") >= start_date AND DATE_FORMAT(NOW(), "%Y-%m-%d") <= expiry_date')
            ->orderBy('id', 'ASC')
            ->first();

            DB::table('tax_invoices')
            ->whereId($latest_tax->id)
            ->update([
                'invoice_id' => $id
            ]);
          }
      }

      $in=DB::table('invoices')
      ->leftJoin(DB::raw('(select sum(amount) as taxs, header_id from invoice_taxes group by header_id) ix'),'ix.header_id','invoices.id')
      ->leftJoin(DB::raw('(select sum(total_price+ppn-discount) as total_price, header_id from invoice_details group by header_id) idd'),'idd.header_id','invoices.id')
      ->where('invoices.id', $id)
      ->selectRaw('
      invoices.*,
      sum(idd.total_price+ix.taxs) as grand_total
      ')
      ->groupBy('invoices.id')
      ->first();
      // dd($in);
      $j=Journal::create([
        'company_id' => $in->company_id,
        'type_transaction_id' => 26, //invoice
        'relation_id' => $in->id,
        'date_transaction' => Carbon::parse($request->journal_date),
        'created_by' => auth()->id(),
        'code' => $in->code,
        'description' => 'Pendapatan atas Invoice No. '.$in->code,
        'status' => 2
      ]);
      JournalDetail::where('header_id', $j->id)->delete();

      $sql_invoice_jo="
      SELECT
        IF(work_orders.is_job_packet != 1, services.account_sale_id, direct_services.account_sale_id) AS account_sale_id,
        IF(work_orders.is_job_packet != 1, services.id, direct_services.id) as service_id,
        CONCAT('layanan ', IF(work_orders.is_job_packet != 1, services.name, direct_services.name)) AS name,
        job_orders.code,
        sum( invoice_details.total_price ) AS total,
        sum(ix.taxs+invoice_details.ppn) as ppn,
        invoice_details.job_order_id,
        invoice_details.job_order_detail_id,
        invoice_details.discount as discount
      FROM
        invoice_details
        LEFT JOIN job_orders ON job_orders.id = invoice_details.job_order_id
        LEFT JOIN work_orders ON work_orders.id = job_orders.work_order_id
        LEFT JOIN services ON services.id = job_orders.service_id
        LEFT JOIN services AS direct_services ON direct_services.id = invoice_details.service_id
        LEFT JOIN (select sum(amount) as taxs, invoice_detail_id from invoice_taxes group by invoice_detail_id) ix on ix.invoice_detail_id = invoice_details.id
      WHERE invoice_details.header_id = $id and invoice_details.cost_type_id is null
      GROUP BY
        invoice_details.id
      ";
      $sql_invoice_ct="
      SELECT
        cost_types.akun_kas_hutang,
        cost_types.akun_uang_muka,
        cost_types.akun_biaya,
        cost_types.name,
        sum( invoice_details.total_price ) AS total,
        sum(ix.taxs+invoice_details.ppn) as ppn,
        invoice_details.discount as discount,
        invoice_details.job_order_id
      FROM
        invoice_details
        LEFT JOIN cost_types ON cost_types.id = invoice_details.cost_type_id
        LEFT JOIN (select sum(amount) as taxs, invoice_detail_id from invoice_taxes group by invoice_detail_id) ix on ix.invoice_detail_id = invoice_details.id
      WHERE
        invoice_details.header_id = $id
        AND invoice_details.cost_type_id is not null
      GROUP BY
        invoice_details.id
      ";
      $sql_tax_inline="
      SELECT
        taxes.pemotong_pemungut,
        taxes.akun_penjualan,
        taxes.name,
        job_orders.code,
        sum( amount ) AS total
      FROM
        invoice_taxes
        LEFT JOIN taxes ON taxes.id = invoice_taxes.tax_id
        LEFT JOIN invoice_details ON invoice_details.id = invoice_taxes.invoice_detail_id
        LEFT JOIN job_orders ON job_orders.id = invoice_details.job_order_id
      WHERE
        invoice_taxes.tax_id IS NOT NULL
        AND invoice_details.header_id = $id
      GROUP BY
        invoice_taxes.tax_id
      ";

      $ket = 'JO';
      $diskon=InvoiceDetail::where('header_id', $id)->sum('discount');
      $ppn=InvoiceDetail::where('header_id', $id)->sum('ppn');
      $invoice_jo=DB::select($sql_invoice_jo);
      $invoice_jo = collect($invoice_jo)->map(function($val){
            if($val->job_order_id && $val->job_order_detail_id) {
                if(SalesOrder::hasJobOrder($val->job_order_id)) {
                    $ket = 'SO';
                    $jod = JOD::show($val->job_order_detail_id);
                    $item = Item::show($jod->item_id);
                    $val->account_sale_id = $item->account_sale_id;
                    $val->name = $item->name;
                }
            }
            return $val;
      })->toArray();
      $invoice_ct=DB::select($sql_invoice_ct);
      // dd($invoice_jo);
      $tax=DB::select($sql_tax_inline);
      if ($in->type_bayar==1) {
        $code = new TransactionCode($in->company_id, 'cashIn');
        $code->setCode();
        $trx_code = $code->getCode();

        $account=Account::find($in->account_cash_id);
        $i=CashTransaction::create([
          'company_id' => $in->company_id,
          'type_transaction_id' => 26,
          'relation_id' => $in->id,
          'code' => $trx_code,
          'reff' => $in->code,
          'jenis' => 1,
          'type' => $account->no_cash_bank,
          'description' => 'Pendapatan Invoice '.$in->code,
          'total' => 0,
          'account_id' => $in->account_cash_id,
          'date_transaction' => Carbon::parse($request->journal_date),
          'status_cost' => 3,
          'created_by' => auth()->id(),
          'journal_id' => $j->id
        ]);

        CashTransaction::where('relation_id', $i->id)->delete();
      } else {
        $i=Receivable::create([
          'company_id' => $in->company_id,
          'contact_id' => $in->customer_id,
          'type_transaction_id' => 26,
          'journal_id' => $j->id,
          'relation_id' => $in->id,
          'created_by' => auth()->id(),
          'code' => $in->code,
          'date_transaction' => Carbon::parse($request->journal_date),
          'date_tempo' => Carbon::parse($in->date_invoice)->addDays($in->termin),
          'description' => 'Pendapatan Invoice No. '.$in->code
        ]);

        ReceivableDetail::where('header_id', $i->id)->delete();
        ReceivableDetail::create([
          'header_id' => $i->id,
          'journal_id' => $j->id,
          'type_transaction_id' => 26,
          'relation_id' => $in->id,
          'code' => $in->code,
          'date_transaction' => Carbon::parse($request->journal_date),
        ]);

        Invoice::find($in->id)->update([
            'receivable_id' => $i->id
        ]);
      }
      $is_error=false;
      $total_in_jo=0;
      foreach ($invoice_jo as $key => $value) {
        if (($value->account_sale_id ?? null) === null) {
          $err_message="Akun pendapatan / penjualan untuk $value->name belum ditentukan!<br>";
          $is_error=true;
          return Response::json(['message' => $err_message], 421);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value->account_sale_id,
          'credit' => $value->total,
          'description' => 'Pendapatan Invoice '.$in->code.' untuk ' . $ket .' '.$value->code.' layanan '.$value->name,
        ]);
        if ($in->type_bayar==1) {
          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $value->account_sale_id,
            'contact_id' => $in->customer_id,
            'amount' => ($value->total+$value->ppn-$value->discount),
            'description' => 'Pendapatan Invoice '.$in->code.' untuk '. $ket .' '.$value->code.' layanan '.$value->name,
          ]);
          CashTransaction::find($i->id)->update([
            'total' => DB::raw('total+'.($value->total + $value->ppn - $value->discount))
          ]);
        } else {
          ReceivableDetail::whereRaw("header_id = $i->id")->update([
            'debet' => DB::raw('debet+'.($value->total+$value->ppn-$value->discount))
          ]);
        }
        $total_in_jo+=$value->total;
      }
      $total_in_ct=0;
      foreach ($invoice_ct as $key => $value) {
        if (empty($account_defaults->pendapatan_reimburse)) {
          $err_message="- Akun Default Pendapatan Reimburse belum ditentukan!<br>";
          $is_error=true;
          return Response::json(['message' => $err_message], 421);
          continue;
        }
        if ($value->job_order_id) {
          // JIKA DARI REIMBURSE
          JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $account_defaults->pendapatan_reimburse,
            'credit' => $value->total,
            'description' => 'Pendapatan Invoice '.$in->code.' untuk jenis biaya '.$value->name,
          ]);
        } else {
          // JIKA BIAYA TAMBAHAN INVOICE
          JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $value->akun_biaya,
            'credit' => $value->total,
            'description' => 'Pendapatan Invoice '.$in->code.' untuk jenis biaya '.$value->name,
          ]);
        }
        if ($in->type_bayar==1) {
          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $value->akun_kas_hutang,
            'contact_id' => $in->customer_id,
            'amount' => ($value->total+$value->ppn-$value->discount),
            'description' => 'Pendapatan Invoice '.$in->code.' untuk jenis biaya '.$value->name,
          ]);
        } else {
          ReceivableDetail::whereRaw("header_id = $i->id")->update([
            'debet' => DB::raw('debet+'.($value->total+$value->ppn-$value->discount))
          ]);
        }

        $total_in_ct+=$value->total;
      }

      $total_taxes=0;
      foreach ($tax as $key => $value) {
        if (empty($value->akun_penjualan)) {
          $err_message="- Akun Penjualan untuk pajak $value->name belum ditentukan!<br>";
          $is_error=true;
          return Response::json(['message' => $err_message], 421);
          continue;
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value->akun_penjualan,
          'debet' => ($value->pemotong_pemungut==1?abs($value->total):0),
          'credit' => ($value->pemotong_pemungut==2?abs($value->total):0),
          'description' => 'PPn '.$value->name.' untuk Invoice '.$in->code.' No. JO '.$value->code,
        ]);
        if ($value->pemotong_pemungut==1) {
          $total_taxes+=abs($value->total);
        } else {
          $total_taxes-=abs($value->total);
        }
      }

      //kondisi dan jurnal diskon
      if ($diskon>0) {
        $dp=AccountDefault::first();
        if (!$dp->diskon_penjualan) {
          $err_message="- Akun default Diskon Penjualan belum ditentukan!<br>";
          $is_error=true;
          return Response::json(['message' => $err_message], 421);
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $dp->diskon_penjualan,
          'debet' => $diskon,
          'description' => 'Diskon atas Invoice '.$in->code,
        ]);
      }
      //kondisi dan jurnal ppn
      if ($ppn>0) {
        $dp=AccountDefault::first();
        if (!$dp->ppn_out) {
          $err_message="Akun default PPN Keluaran belum ditentukan!<br>";
          return Response::json(['message'=> $err_message], 421);
          $is_error=true;
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $dp->ppn_out,
          'credit' => $ppn,
          'description' => 'PPN atas Invoice '.$in->code,
        ]);
      }

      //jurnal kas / hutang
      $total_kas_hutang=$total_in_jo+$total_in_ct+$total_taxes-$diskon-$total_taxes;
      if ($in->type_bayar==1) {
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $in->account_cash_id,
          'debet' => $in->grand_total,
          'description' => 'Pendapatan atas Invoice '.$in->code,
        ]);
      } else {
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $account_defaults->piutang,
          'debet' => $in->grand_total,
          'description' => 'Pendapatan atas Invoice '.$in->code,
        ]);
      }

      //jurnal biaya ----------------------------------------------<<

      // $sql_cost="
      // SELECT
      //  SUM(job_order_costs.total_price) as total,
      //  CONCAT(cost_types.name,' - No. JO',job_orders.code) as name,
      //  cost_types.akun_biaya,
      //  cost_types.akun_uang_muka
      // FROM
      //  job_order_costs
      //  LEFT JOIN job_orders ON job_orders.id = job_order_costs.header_id
      //  LEFT JOIN invoice_details on job_orders.id = invoice_details.job_order_id
      //  LEFT JOIN cost_types on job_order_costs.header_id = cost_types.id
      //  WHERE invoice_details.header_id = $id
      // GROUP BY job_orders.id, cost_types.id
      // ";
      // $biaya=DB::select($sql_cost);
      // $jj=Journal::create([
      //   'company_id' => $in->company_id,
      //   'type_transaction_id' => 26, //invoice
      //   'date_transaction' => Carbon::parse($request->journal_date),
      //   'created_by' => auth()->id(),
      //   'code' => $in->code,
      //   'description' => 'Biaya atas invoice '.$in->code,
      // ]);
      // foreach ($biaya as $key => $value) {
      //   JournalDetail::create([
      //     'header_id' => $jj->id,
      //     'account_id' => $value->akun_biaya,
      //     'debet' => $value->total,
      //     'description' => 'Biaya '.$value->name,
      //   ]);
      //   JournalDetail::create([
      //     'header_id' => $jj->id,
      //     'account_id' => $value->akun_uang_muka,
      //     'credit' => $value->total,
      //     'description' => 'Biaya '.$value->name,
      //   ]);
      // }

      //end jurnal biaya---------------------------------------------->>

      Invoice::find($in->id)->update([
        'status' => 3,
        'journal_id' => $j->id
      ]);

      if ($is_error) {
        return Response::json(['message' => $err_message],500);
      }

      DB::commit();
    }

    public function approve($id)
    {
      DB::beginTransaction();
      Invoice::find($id)->update([
        'approve_by' => auth()->id(),
        'is_approve' => 1,
        'date_approve' => Carbon::now(),
        'status' => 2
      ]);
      DB::commit();
    }

    public function print(Request $request, $id)
    {
      /*
        1 => 'INVOICE WO GABUNGAN',
        2 => 'INVOICE WO PERSATUAN',
        3 => 'INVOICE INTER ISLAND',
        4 => 'INVOICE TRUCKING',
        5 => 'INVOICE PROJECT',
      */

      $format=$request->format;
      $i = Invoice::find($id);
      if($i->printed_amount == null) {
          $i->printed_amount = '[0, 0, 0, 0, 0]';
          $i->save();
      }

      $printed_amount = json_decode($i->printed_amount);
      $printed_amount[$format - 1] += 1;
      $i->printed_amount = json_encode($printed_amount);
      $i->save();
      if ($format==1) {
        $response=$this->print_format_1($id);
      } else if ($format==2) {
        $response=$this->print_format_2($id, 0, $request->show_ppn);
      } else if ($format==3) {
        $response=$this->print_format_3($id, 0, $request->show_ppn);
      } else if ($format==4) {
        $response=$this->print_format_4($id, 0, $request->show_ppn);
      } else if ($format==5) {
        $response=$this->print_format_5($id, $request->show_ppn);
      }
      return $response;
    }
    public function print_format_1($id)
    {
      $data['item']=$i;

      $data['details']=InvoiceDetail::where('header_id', $id)
      ->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->selectRaw('
        invoice_details.*,
        min(job_orders.code) as code_inv,
        job_orders.no_bl as bl,
        count(job_orders.work_order_id) as totalJO,
        sum(if(job_orders.service_type_id in (1,2,3),invoice_details.total_price,0)) as trucking2,
        sum(if(job_orders.service_type_id not in (1,2,3),invoice_details.total_price,0)) as custom_all2,
        sum(invoice_details.ppn)+(select sum(amount) from invoice_taxes as itx where itx.invoice_detail_id = invoice_details.id) as total_ppn,
        (
          select sum(jo_customAll.total_price)
          from job_orders as jo_customAll
          where jo_customAll.work_order_id = job_orders.work_order_id
          and jo_customAll.service_type_id not in (1,2,3)
        ) as custom_all,
        (
          select sum(jo_trucking.total_price)
          from job_orders as jo_trucking
          where jo_trucking.work_order_id = job_orders.work_order_id
          and jo_trucking.service_type_id in (1,2,3)
        ) as trucking
      ')
      ->groupBy('job_orders.work_order_id')
      ->get();
      // dd($data);
      $data['remark']=$this->remark;
      return PDF::loadView('pdf.invoice.wo-gabungan', $data)
                ->stream();
    }
    public function print_format_2($id,$is_gabungan=0, $show_ppn)
    {
      $i = Invoice::find($id);
      $printed_amount = json_decode($i->printed_amount) ?? [];
      $data['label'] = ($printed_amount[1] ?? 1) == 1 ? 'ASLI' : 'COPY';
      $data['item']=$i;
      $wolist=DB::table('invoice_details')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->where('header_id', $id)
      ->where('work_orders.id','!=',null)
      ->selectRaw('job_orders.work_order_id,work_orders.code as wo_code')
      ->groupBy('job_orders.work_order_id')
      ->get();

      $data['ppn_total'] = DB::table('invoice_taxes AS IT')
      ->leftJoin('taxes AS T', 'T.id', 'IT.tax_id')
      ->leftJoin('invoice_details AS ID', 'ID.id', 'IT.invoice_detail_id')
      ->where('T.is_ppn', 1)
      ->where('ID.header_id', $id)
      ->sum('IT.amount');

      $data['other_tax'] = DB::table('invoice_taxes AS IT')
      ->leftJoin('taxes AS T', 'T.id', 'IT.tax_id')
      ->leftJoin('invoice_details AS ID', 'ID.id', 'IT.invoice_detail_id')
      ->where('T.is_ppn', 0)
      ->where('ID.header_id', $id)
      ->sum('IT.amount');

      $data['details']=[];
      foreach ($wolist as $key => $value) {
        // dd($value);
        if (!$value->work_order_id) {
          continue;
        }
        $addon=DB::select("select group_concat(distinct job_orders.aju_number) as aju,group_concat(distinct job_orders.no_bl) as bl, GROUP_CONCAT(distinct work_orders.code) as code_wo,concat(max(job_orders.vessel_name),'  ',max(job_orders.voyage_no)) as vessel from invoice_details left join job_orders on job_orders.id = invoice_details.job_order_id left join work_orders on work_orders.id = job_orders.work_order_id where work_orders.id = $value->work_order_id")[0];
        $trucking=DB::table('invoice_details')
        ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('routes','routes.id','job_orders.route_id')
        ->leftJoin('container_types','container_types.id','job_orders.container_type_id')
        ->leftJoin('vehicle_types','vehicle_types.id','job_orders.vehicle_type_id')
        ->whereRaw("job_orders.service_type_id in (1,2,3) and header_id = $id and job_orders.work_order_id = $value->work_order_id and invoice_details.cost_type_id is null")
        ->selectRaw("sum(invoice_details.total_price) as total_price, sum(invoice_details.qty) as qty,
        services.name as service,routes.name as trayek, sum(invoice_details.ppn) as ppn, concat(container_types.code) as ctype, vehicle_types.name as vtype, invoice_details.commodity_name")
        ->groupBy('job_orders.service_id','job_orders.route_id','job_orders.container_type_id','job_orders.vehicle_type_id')
        ->orderBy('job_orders.work_order_detail_id','asc')
        ->get();
        $ff=DB::table('invoice_details')
        ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('pieces','pieces.id','job_orders.piece_id')
        ->whereRaw("job_orders.service_type_id not in (1,2,3) and header_id = $id and job_orders.work_order_id = $value->work_order_id and invoice_details.cost_type_id is null")
        ->selectRaw("
        invoice_details.commodity_name,
        invoice_details.price as price,
        sum(invoice_details.total_price) as total_price,
        sum(invoice_details.qty) as qty,services.name as service,
        sum(invoice_details.ppn) as ppn,
        sum(invoice_details.discount) as discount,
        pieces.name as piece
        ")
        ->groupBy('services.id','job_orders.piece_id')
        ->orderBy('job_orders.work_order_detail_id','asc')
        // ->orderBy('services.id','desc')
        ->get();
        $data['pol_pod']=DB::select("select
        max(cstart.name) as pol,
        max(cend.name) as pod,
        max(vessels.name) as vessel,
        max(voyage_schedules.voyage) as voyage
        from invoice_details
        left join job_orders on job_orders.id = invoice_details.job_order_id
        left join job_order_details on job_order_details.header_id = job_orders.id
        left join manifest_details on manifest_details.job_order_detail_id = job_order_details.id
        left join manifests on manifests.id = manifest_details.header_id
        left join containers on containers.id = manifests.container_id
        left join voyage_schedules on voyage_schedules.id = containers.voyage_schedule_id
        left join vessels on vessels.id = voyage_schedules.vessel_id
        left join cities as cstart on cstart.id = voyage_schedules.pol_id
        left join cities as cend on cend.id = voyage_schedules.pod_id
        ")[0];
        // dd($ff);
        $data['details'][$key]['detail_trucking']=$trucking;
        $data['details'][$key]['detail_ff']=$ff;
        $data['details'][$key]['work_order_code']=$value->wo_code;
        $data['details'][$key]['addon']=$addon;
        if ($key==0) {
          $lain=DB::table('invoice_details')
          ->leftJoin('cost_types','cost_types.id','invoice_details.cost_type_id')
          ->whereRaw("header_id = $id and cost_type_id is not null")
          ->selectRaw('
          cost_types.id,
          cost_types.name,
          sum(invoice_details.total_price) as total_price,
          invoice_details.ppn
          ')
          ->groupBy('invoice_details.cost_type_id')
          ->get();
          // dd($lain);
          if (count($lain)>0) {
            $data['details'][$key]['detail_other']=$lain;
          }
        }
      }
      // dd($data);
      if ($is_gabungan) {
        return $data;
      }
        $data['show_ppn'] = $show_ppn ?? 0;
        $data['remark'] = $this->remark;
        return PDF::loadView('pdf.invoice.wo-persatuan', $data)
                  ->setPaper('A5')

                  ->stream('Invoice.pdf');
    }
    public function print_format_3_bkp($id)
    {
      $data['item']=Invoice::find($id);
      $data['details']=DB::table('invoice_details')
      ->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->leftJoin('services', 'services.id', 'job_orders.service_id')
      ->leftJoin('routes', 'routes.id', 'job_orders.route_id')
      ->leftJoin('pieces', 'pieces.id', 'job_orders.piece_id')
      ->where('invoice_details.header_id', $id)
      ->whereIn('job_orders.service_type_id', [1,2,3])
      ->selectRaw('
        invoice_details.*,
        job_orders.work_order_id,
        services.name as service,
        routes.name as route,
        pieces.name as piece,
        count(job_orders.work_order_id) as totalJO,
        (select group_concat(distinct wos.code) from invoice_details as ids left join job_orders as jos on jos.id = ids.job_order_id left join work_orders as wos on wos.id = jos.work_order_id where ids.header_id = invoice_details.id) as no_wo,
        sum(invoice_details.ppn) as ppn
      ')
      ->groupBy('job_orders.work_order_id')
      ->get();
      $data['container']=DB::table('manifest_details')
      ->leftJoin('job_order_details','job_order_details.id','manifest_details.job_order_detail_id')
      ->leftJoin('manifests','manifests.id','manifest_details.header_id')
      ->leftJoin('job_orders','job_orders.id','job_order_details.header_id')
      ->leftJoin('invoice_details','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('containers','containers.id','manifests.container_id')
      ->leftJoin('container_types','container_types.id','job_orders.container_type_id')
      ->whereRaw("invoice_details.header_id = $id")
      ->selectRaw('concat(container_types.code) as container, count(distinct manifests.id) as total')
      ->groupBy('containers.id')->get();
      $data['tax']=DB::table('invoice_taxes')->leftJoin('taxes','taxes.id','invoice_taxes.tax_id')->where('invoice_taxes.header_id', $id)->selectRaw('taxes.name, sum(invoice_taxes.amount) as amount')->groupBy('invoice_taxes.header_id')->first();
      // dd($data);
      // hitung container
      $container = 0;
      $data['remark'] = $this->remark;
      foreach ($data['details'] as $key => $detail) {
        if(!empty($detail->job_order->detail)){
          foreach($detail->job_order->detail as $jod){
            if (!empty($jod->manifest->container)) {
              $container++;
            }
          }
        }
      }

        if ($container <= 10) {
            return PDF::loadView('pdf.invoice.inter-island-1', $data)
                      ->setPaper('a5')

                      ->stream();
        } else {
            return PDF::loadView('pdf.invoice.inter-island-2', $data)
                      ->setPaper('a5')

                      ->stream();
        }
    }
    public function print_format_3($id,$is_gabungan=0, $show_ppn)
    {
      $i = Invoice::find($id);
      $printed_amount = json_decode($i->printed_amount);
      $data['label'] = $printed_amount[2] == 1 ? 'ASLI' : 'COPY';
      $data['item']=$i;
      // $data['details']=InvoiceDetail::leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      // ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      // ->where('invoice_details.header_id', $id)
      // ->whereIn('job_orders.service_type_id', [1,2,3])
      // ->selectRaw('
      //   invoice_details.*, job_orders.work_order_id,
      //   count(job_orders.work_order_id) as totalJO
      // ')
      // ->groupBy('job_orders.work_order_id')
      // ->get();

      $data['details']=DB::table('invoice_details')
      ->leftJoin('manifests','manifests.id','invoice_details.manifest_id')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->leftJoin('cities as city_start','city_start.id','routes.city_from')
      ->leftJoin('cities as city_end','city_end.id','routes.city_to')
      ->leftJoin('container_types','container_types.id','job_orders.container_type_id')
      ->leftJoin('vehicle_types','vehicle_types.id','job_orders.vehicle_type_id')
      ->leftJoin('services','services.id','job_orders.service_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->whereRaw("invoice_details.header_id = $id and invoice_details.job_order_id is not null and invoice_details.cost_type_id is null")
      ->selectRaw('
      sum(if(job_orders.service_type_id=1,job_orders.total_unit,invoice_details.qty)) as qty,
      sum(invoice_details.total_price) as total_price,
      sum(invoice_details.ppn) as ppn,
      if(job_orders.service_type_id=1,sum(invoice_details.total_price),invoice_details.price) as price,
      vehicle_types.name as vname,
      container_types.code as cname,
      group_concat(distinct job_orders.id) as jo_list,
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as name,
      CONCAT(services.name,"<br>",routes.name) as trayek,
      city_start.name as city_start,
      city_end.name as city_end,
      group_concat(distinct work_orders.code SEPARATOR \'<br>\') as code_wo,
      manifests.vehicle_type_id,
      manifests.container_type_id
      ')
      ->groupBy('manifests.vehicle_type_id')
      ->get();

        $data['po'] = DB::table('job_orders as jo')
            ->leftJoin('invoice_details as invd','invd.job_order_id','jo.id')
            ->selectRaw('group_concat(distinct jo.no_po_customer) as po_customer')
            ->where('invd.header_id', $id)
            ->groupBy('jo.id')
            ->first();

      $data['additional']=DB::table('invoice_details')
      ->leftJoin('cost_types','cost_types.id','invoice_details.cost_type_id')
      ->whereRaw("invoice_details.header_id = $id and cost_types.id is not null")
      ->selectRaw('cost_types.name, invoice_details.total_price')->get();
      $data['manifests']=DB::table('invoice_details')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('job_order_details','job_orders.id','job_order_details.header_id')
      ->leftJoin('manifest_details','manifest_details.job_order_detail_id','job_order_details.id')
      ->leftJoin('manifests','manifests.id','manifest_details.header_id')
      ->leftJoin('delivery_order_drivers','manifests.id','delivery_order_drivers.manifest_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->leftJoin('cities as city_start','city_start.id','routes.city_from')
      ->leftJoin('cities as city_end','city_end.id','routes.city_to')
      ->leftJoin('container_types','container_types.id','manifests.container_type_id')
      ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
      ->leftJoin('containers','containers.id','manifests.container_id')
      ->leftJoin('contacts as driver','driver.id','delivery_order_drivers.driver_id')
      ->whereRaw("invoice_details.header_id = $id and invoice_details.manifest_id is not null")
      ->selectRaw('
      container_types.code as name,
      invoice_details.price,
      containers.container_no,
      (select GROUP_CONCAT(jbdr.item_name) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as item_name,
      (select MAX(jbdr.weight) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as tonase,
      (select if(SUM(mds.transported)=0,1,SUM(mds.transported)) from manifest_details as mds where mds.header_id = manifests.id) as qty,
      IF(driver.id is not null,driver.name,delivery_order_drivers.driver_name) as driver,
      group_concat(distinct job_orders.no_po_customer) as po_customer,
      delivery_order_drivers.code as no_sj,
      city_start.name as city_start,
      city_end.name as city_end,
      IF(vehicles.id is not null,vehicles.nopol,delivery_order_drivers.nopol) as nopol,
      work_orders.code as code_wo
      ')
      ->groupBy('manifests.id')->get();
      $data['tax']=DB::table('invoice_taxes')->leftJoin('taxes','taxes.id','invoice_taxes.tax_id')->where('invoice_taxes.header_id', $id)->selectRaw('taxes.name, sum(invoice_taxes.amount) as amount')->groupBy('invoice_taxes.header_id')->first();


        // hitung container
        // $container = 0;
        // foreach ($data['manifests'] as $key => $manifest) {
        //   for ($i=0; $i < 100; $i++) {
        //     $data['manifests'][] = $manifest;
        //   }
        // }
        // dd($data);
        if ($is_gabungan) {
            return $data;
        }
        $data['show_ppn'] = $show_ppn ?? 0;
        $data['remark'] = $this->remark;
        return PDF::loadView('pdf.invoice.inter-island-rev', $data)
                  ->setPaper('a5')

                  ->stream();
    }

    public function print_format_4($id,$is_gabungan=0, $show_ppn)
    {
      $i = Invoice::find($id);
      $printed_amount = json_decode($i->printed_amount) ?? [];
      $data['label'] = ( $printed_amount[3] ?? 1 ) == 1 ? 'ASLI' : 'COPY';
      $data['item']=$i;
      // $data['details']=InvoiceDetail::leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      // ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      // ->where('invoice_details.header_id', $id)
      // ->whereIn('job_orders.service_type_id', [1,2,3])
      // ->selectRaw('
      //   invoice_details.*, job_orders.work_order_id,
      //   count(job_orders.work_order_id) as totalJO
      // ')
      // ->groupBy('job_orders.work_order_id')
      // ->get();

      $data['details']=DB::table('invoice_details')
      ->leftJoin('manifests','manifests.id','invoice_details.manifest_id')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->leftJoin('cities as city_start','city_start.id','routes.city_from')
      ->leftJoin('cities as city_end','city_end.id','routes.city_to')
      ->leftJoin('container_types','container_types.id','job_orders.container_type_id')
      ->leftJoin('vehicle_types','vehicle_types.id','job_orders.vehicle_type_id')
      ->leftJoin('services','services.id','job_orders.service_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->whereRaw("invoice_details.header_id = $id and invoice_details.job_order_id is not null and invoice_details.cost_type_id is null")
      ->selectRaw('
      sum(if(job_orders.service_type_id!=1,1,invoice_details.qty)) as qty,
      sum(invoice_details.total_price) as total_price,
      sum(invoice_details.ppn) as ppn,
      invoice_details.price,
      vehicle_types.name as vname,
      container_types.code as cname,
      job_orders.service_type_id,
      group_concat(distinct job_orders.id) as jo_list,
      if(job_orders.service_type_id=1,invoice_details.imposition_name,if(vehicle_types.id is not null,vehicle_types.name,container_types.code)) as name,
      CONCAT(services.name,"<br>",routes.name) as trayek,
      city_start.name as city_start,
      city_end.name as city_end,
      group_concat(distinct work_orders.code SEPARATOR \'<br>\') as code_wo,
      manifests.vehicle_type_id,
      manifests.container_type_id
      ')
      ->groupBy('job_orders.service_id')
      ->get();
      $data['po']=DB::table('job_orders')
      ->leftJoin('invoice_details','invoice_details.job_order_id','job_orders.id')
      ->selectRaw('group_concat(distinct job_orders.no_po_customer) as po_customer')
      ->where('invoice_details.header_id', $id)
      ->groupBy('job_orders.id')
      ->first();

      $data['additional']=DB::table('invoice_details')
      ->leftJoin('cost_types','cost_types.id','invoice_details.cost_type_id')
      ->whereRaw("invoice_details.header_id = $id and cost_types.id is not null")
      ->selectRaw('cost_types.name, invoice_details.total_price')->get();
      $data['manifests']=DB::table('invoice_details')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('job_order_details','job_orders.id','job_order_details.header_id')
      ->leftJoin('manifest_details','manifest_details.job_order_detail_id','job_order_details.id')
      ->leftJoin('manifests','manifests.id','manifest_details.header_id')
      ->leftJoin('delivery_order_drivers','manifests.id','delivery_order_drivers.manifest_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->leftJoin('cities as city_start','city_start.id','routes.city_from')
      ->leftJoin('cities as city_end','city_end.id','routes.city_to')
      ->leftJoin('container_types','container_types.id','manifests.container_type_id')
      ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
      ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
      ->leftJoin('contacts as driver','driver.id','delivery_order_drivers.driver_id')
      ->whereRaw("invoice_details.header_id = $id and invoice_details.manifest_id is not null")
      ->selectRaw('
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as name,
      invoice_details.price,
      (select GROUP_CONCAT(jbdr.item_name) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as item_name,
      (select MAX(jbdr.weight) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as tonase,
      (select if(SUM(mds.transported)=0,1,SUM(mds.transported)) from manifest_details as mds where mds.header_id = manifests.id) as qty,
      IF(driver.id is not null,driver.name,delivery_order_drivers.driver_name) as driver,
      group_concat(distinct job_orders.no_po_customer) as po_customer,
      delivery_order_drivers.code as no_sj,
      city_start.name as city_start,
      city_end.name as city_end,
      IF(vehicles.id is not null,vehicles.nopol,delivery_order_drivers.nopol) as nopol,
      work_orders.code as code_wo
      ')
      ->groupBy('manifests.id')->get();
      $data['tax']=DB::table('invoice_taxes')->leftJoin('taxes','taxes.id','invoice_taxes.tax_id')->where('invoice_taxes.header_id', $id)->selectRaw('taxes.name, sum(invoice_taxes.amount) as amount')->groupBy('invoice_taxes.header_id')->first();


      // hitung container
      // $container = 0;
      // foreach ($data['manifests'] as $key => $manifest) {
      //   for ($i=0; $i < 100; $i++) {
      //     $data['manifests'][] = $manifest;
      //   }
      // }
      // dd($data);
      if ($is_gabungan) {
        return $data;
      }
      // return view('pdf.invoice.trucking2',$data);
        $data['show_ppn'] = $show_ppn ?? 0;
        $data['remark'] = $this->remark;
        return PDF::loadView('pdf.invoice.trucking2', $data)
                  ->setPaper('a5')

                  ->stream();
    }
    public function print_format_5($id, $show_ppn)
    {
      $i = Invoice::find($id);
      $printed_amount = json_decode($i->printed_amount);
      $data['label'] = $printed_amount[4] == 1 ? 'ASLI' : 'COPY';
      $data['item']=$i;
      $data['details']=InvoiceDetail::leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->where('invoice_details.header_id', $id)
      ->selectRaw('
        invoice_details.*, job_orders.work_order_id,
        count(job_orders.work_order_id) as totalJO
      ')
      ->groupBy('job_orders.work_order_id')
      ->get();
      $data['wo']=DB::table('invoice_details')
      ->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->where('invoice_details.header_id', $id)
      ->selectRaw('
      group_concat(distinct work_orders.code) as wo_code
      ')
      ->first();
      // dd($data);
      // hitung container
      $container = 0;
      foreach ($data['details'] as $key => $detail) {
        if(!empty($detail->job_order->detail)){
          foreach($detail->job_order->detail as $jod){
            if (!empty($jod->manifest->container)) {
              $container++;
            }
          }
        }
      }
        $data['show_ppn'] = $show_ppn ?? 0;
        $data['remark'] = $this->remark;
        return PDF::loadView('pdf.invoice.project', $data)
                  ->setPaper('a5')

                  ->stream();
    }
    public function cari_jo_cost(Request $request)
    {
      // dd($request);
      $input=$request->input();
      $jo_list="0";
      foreach ($input as $key => $value) {
        if (empty($value)) {
          continue;
        } elseif (empty($value['job_order_id'])) {
          continue;
        }
        $jo_list.=','.$value['job_order_id'];
      }
      // dd($jo_list);
      $data=DB::table('job_order_costs')
      ->leftJoin('job_orders','job_orders.id','=','job_order_costs.header_id')
      ->leftJoin('cost_types','cost_types.id','=','job_order_costs.cost_type_id')
      ->whereRaw("job_order_costs.type = 2 and job_order_costs.header_id in ($jo_list)")
      ->select([
        'job_order_costs.cost_type_id as id',
        'job_order_costs.qty',
        'job_order_costs.price',
        'job_order_costs.total_price',
        'job_order_costs.vendor_id',
        'job_order_costs.header_id as job_order_id',
        'cost_types.name as name',
        'job_orders.code as parent'
      ])->get();
      // dd($data);
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function cari_invoice(Request $request)
    {
      $invoice_list = DB::table('invoices')->leftJoin('contacts','contacts.id','invoices.customer_id')
      // ->groupBy('invoices.customer_id')
      // ->selectRaw('contacts.id,contacts.name,group_concat(invoices.id) as invoice_list')
      ->selectRaw('invoices.id')
      ->where('contacts.id', $request->customer_id)
      ->get()
      ->pluck('id');
      $data=DB::table('invoices')
      ->leftJoin(DB::raw('(select invoice_details.header_id, group_concat(distinct jo.aju_number) as aju, group_concat(distinct jo.no_bl) as bl, group_concat(distinct jo.no_po_customer) as po_customer from invoice_details left join job_orders as jo on jo.id = invoice_details.job_order_id group by invoice_details.header_id) as Y'),'Y.header_id','invoices.id')
      // ->whereRaw("invoices.id in ($request->invoice_list)")
      ->whereIn("invoices.id", $invoice_list)
      ->selectRaw("
      id,
      code,
      Y.aju,
      Y.bl,
      Y.po_customer,
      grand_total,
      date_invoice,
      IF(printed_amount IS NOT NULL, 1, 0) AS is_printed
      ")
      ->get();
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function print_wo_gabungan(Request $request)
    {
      // dd($request);
      $request->validate([
        'list' => 'required',
        'type_wo' => 'required'
      ]);
      $data['item']=Invoice::whereRaw("id in ($request->list)")->first();
      $lastSerial=DB::table('invoice_joins')->groupBy('serial')->selectRaw('serial')->orderBy('serial','desc')->first();
      // dd($lastSerial);
      $exp=explode(",",$request->list);
      DB::beginTransaction();

      foreach ($exp as $key => $value) {
        $serial=($lastSerial?$lastSerial->serial:0);
        $i=Invoice::find($value);
        if($i->printed_amount == null) {
            $i->printed_amount = '[0, 0, 0, 0, 0]';
            $i->save();
        }

        if ($i->id==$exp[0]) {
          $code=$i->code;
        }
        InvoiceJoin::create([
          'serial' => $serial+1,
          'invoice_id' => $value,
          'type_wo' => $request->type_wo
        ]);
        $i->update([
          'code' => $code.'-'.($key+1),
          'is_join' => 1,
        ]);
      }
      $data['details']=InvoiceDetail::whereRaw("header_id in ($request->list)")
      ->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->leftJoin('invoices', 'invoices.id', 'invoice_details.header_id')
      ->selectRaw('
        invoice_details.*,
        min(invoices.code) as code_invoice,
        job_orders.no_bl as bl,
        count(job_orders.work_order_id) as totalJO,
        group_concat(distinct job_orders.no_po_customer) as po_customer,
        sum(if(invoice_details.job_order_id is null,invoice_details.total_price,0)) as reimburse,
        sum(if(job_orders.service_type_id in (1,2,3),invoice_details.total_price,0)) as trucking2,
        sum(if(job_orders.service_type_id not in (1,2,3),invoice_details.total_price,0)) as custom_all2,
        sum(invoice_details.ppn)+(select sum(amount) from invoice_taxes as itx where itx.invoice_detail_id = invoice_details.id) as total_ppn,
        (
          select sum(jo_customAll.total_price)
          from job_orders as jo_customAll
          where jo_customAll.work_order_id = job_orders.work_order_id
          and jo_customAll.service_type_id not in (1,2,3)
        ) as custom_all,
        (
          select sum(jo_trucking.total_price)
          from job_orders as jo_trucking
          where jo_trucking.work_order_id = job_orders.work_order_id
          and jo_trucking.service_type_id in (1,2,3)
        ) as trucking
      ')
      ->groupBy('invoice_details.header_id')
      ->get();
      $data['type_wo']=$request->type_wo;

      DB::commit();
      if ($request->type_wo==1) {
        foreach ($data['details'] as $key => $value) {
          $data['lampiran'][$key]=$this->print_format_2($value->header_id,1, 0);
        }
      } else {
        foreach ($data['details'] as $key => $value) {
          $data['lampiran'][$key]=$this->print_format_4($value->header_id,1, 0);
        }
      }
      // dd($data);
      $data['remark']=$this->remark;
        return PDF::loadView('pdf.invoice.wo-gabungan', $data)

                  ->stream();
    }

    public function jo_list(Request $request)
    {
      $data=DB::table('job_orders')
      ->leftJoin('services','services.id','job_orders.service_id')
      ->where('customer_id', $request->customer_id)
      ->selectRaw("
      job_orders.id,
      concat(job_orders.code,' / ',services.name) as name,
      job_orders.code
      ")
      ->orderBy('job_orders.code','desc')->get();

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 01-09-2020
      Description : Menyimpan invoice pada work order
      Developer : Didin
      Status : Create
    */
    public function storeInvoiceInWorkOrder() {
        $invoices = DB::table('invoice_details')
        ->join('work_orders', 'work_orders.id', 'invoice_details.work_order_id')
        ->whereNull('work_orders.invoice_id')
        ->select('invoice_details.header_id AS invoice_id', 'work_orders.id AS work_order_id')
        ->get();

        foreach($invoices as $invoice) {
            DB::table('work_orders')
            ->whereId($invoice->work_order_id)
            ->update([
                'invoice_id' => $invoice->invoice_id
            ]);
        }

        DB::table('work_orders')
        ->whereRaw('id NOT IN (SELECT work_order_id FROM invoice_details WHERE work_order_id IS NOT NULL)')
        ->update([
            'invoice_id' => null
        ]);
    }
}

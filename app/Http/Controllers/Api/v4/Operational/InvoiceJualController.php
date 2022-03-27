<?php

namespace App\Http\Controllers\Api\v4\Operational;

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
use DB;
use Response;
use PDF;
use Carbon\Carbon;

class InvoiceJualController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id')->get();
        $data['company'] = companyAdmin(auth()->id());
        $data['customer_list']=DB::table('invoices')->leftJoin('contacts','contacts.id','invoices.customer_id')->groupBy('invoices.customer_id')->selectRaw('contacts.id,contacts.name,group_concat(invoices.id) as invoice_list')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['customer']=DB::table('contacts')->where('is_pelanggan',1)->select('id','name','company_id')->get();
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
        'date_invoice' => 'required',
        'journal_date' => 'required',
        'customer_id' => 'required',
        'type_bayar' => 'required',
        'cash_account_id' => 'required_if:type_bayar,1',
        'termin' => 'required_if:type_bayar,2',
        'grand_total' => 'required|integer|min:1',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'invoice');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=Invoice::create([
        'company_id' => $request->company_id,
        'customer_id' => $request->customer_id,
        'account_cash_id' => $request->cash_account_id,
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
        'status' => 1,
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

      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $id=InvoiceDetail::create([
          'header_id' => $i->id,
          'job_order_id' => $value['job_order_id'],
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
          'manifest_id' => $value['manifest_id'],
          'create_by' => auth()->id(),
          'imposition_name' => $value['imposition_name']??'-',
          'commodity_name' => $value['commodity_name']??'-',
          'discount' => round($value['discount']),
          'is_ppn' => $value['is_ppn'],
          'ppn' => round($value['ppn']),
        ]);

        foreach ($value['detail_tax'] as $vws) {
          InvoiceTax::create([
            'header_id' => $i->id,
            'invoice_detail_id' => $id->id,
            'tax_id' => $vws['tax_id'],
            'amount' => $vws['value']
          ]);
        }

        if (isset($value['job_order_id'])) {
          JobOrder::find($value['job_order_id'])->update([
            'invoice_id' => $i->id,
            'code_invoice' => $trx_code,
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

      // if ($request->type_bayar==2) {
      //   $r=Receivable::create([
      //     'company_id' => $request->company_id,
      //     'contact_id' => $request->customer_id,
      //     'type_transaction_id' => 26, //invoice
      //     'relation_id' => $i->id,
      //     'created_by' => auth()->id(),
      //     'code' => $trx_code,
      //     'date_transaction' => dateDB($request->date_invoice),
      //     'date_tempo' => Carbon::parse($request->date_invoice)->addDays($request->termin),
      //     'description' => 'Invoice - '.$trx_code,
      //     'debet' => ($request->grand_total+$request->grand_total_additional),
      //   ]);
      //
      //   ReceivableDetail::create([
      //     'header_id' => $r->id,
      //     'type_transaction_id' => 26, //invoice
      //     'code' => $trx_code,
      //     'relation_id' => $i->id,
      //     'date_transaction' => dateDB($request->date_invoice),
      //     'debet' => ($request->grand_total+$request->grand_total_additional),
      //     'description' => 'Invoice - '.$trx_code,
      //   ]);
      // }

      // Journal::create([
      //   'company_id' => $request->company_id,
      //   'type_transaction_id' => 26,
      //   'date_transaction' => dateDB($request->date_invoice),
      //   'created_by' => auth()->id(),
      //   'code' => $trx_code,
      //   'description' => "Invoice Jual - ".$trx_code,
      // ]);
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
      $data['item']=Invoice::with('company','customer')
      ->where('id', $id)
      ->selectRaw('invoices.*')
      ->first();
      $data['detail1']=InvoiceDetail::with('job_order','manifest.container','manifest.vehicle','job_order.commodity','job_order.service','job_order.trayek','cost_type')->where('header_id', $id)->get();
      $data['taxes']=InvoiceTax::where('header_id', $id)->sum('amount');
      $data['addon']=DB::select("select group_concat(distinct job_orders.aju_number) as aju,group_concat(distinct job_orders.no_bl) as bl, GROUP_CONCAT(distinct work_orders.code) as code_wo from invoice_details left join job_orders on job_orders.id = invoice_details.job_order_id
left join work_orders on work_orders.id = job_orders.work_order_id where invoice_details.header_id = $id")[0];
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
      // dd($id);
      // DB::beginTransaction();
      // $jo=Invoice::find($id);

      // $jo->delete();
      // DB::commit();
      $dataManifest=InvoiceDetail::where('header_id', $id)->get();
      // dd($dataManifest);
      foreach ($dataManifest as $key => $value) {
        // echo $value->manifest_id;
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
          services.account_sale_id
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
        // $detail=JobOrderDetail::where('header_id', $jo->id)->get();
        // $sql="
        // SELECT
        // 	manifests.id as manifest_id,
        // 	manifest_details.job_order_detail_id,
        // 	manifest_details.id as manifest_detail_id,
        // 	manifests.CODE as codes,
        // 	contacts.NAME AS driver,
        // 	vehicles.nopol,
        // 	vehicle_types.NAME AS vname,
        // 	(CASE job_order_details.imposition
        // 	WHEN 1 THEN
        // 		job_order_details.volume/(job_order_details.transported+job_order_details.leftover)*manifest_details.transported
        // 	WHEN 2 THEN
        // 		job_order_details.weight/(job_order_details.transported+job_order_details.leftover)*manifest_details.transported
        // 	ELSE
        // 		manifest_details.transported
        // 	END) as qty,
        // 	job_order_details.price,
        //   containers.container_no,
        //   job_order_details.imposition
        // FROM
        // 	manifests
        // 	LEFT JOIN containers ON manifests.container_id = containers.id
        // 	LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
        // 	LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
        // 	LEFT JOIN contacts ON contacts.id = manifests.driver_id
        // 	LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
        // 	LEFT JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id
        // 	LEFT JOIN job_orders ON job_orders.id = manifest_details.header_id
        // WHERE
        // 	manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $id )
        // ";
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
        jo.description
        from job_orders as jo
        left join routes on jo.route_id = routes.id
        left join vehicle_types on jo.vehicle_type_id = vehicle_types.id
        left join (select sum(if(imposition=1,volume,if(imposition=2,weight,qty))) as qty,header_id,imposition,sum(total_price) as total_price,max(price) as price from job_order_details group by header_id) Y on Y.header_id = jo.id
        where jo.id = $id and jo.invoice_id is null";

        $man=DB::select($sql2);
        // dd($man);
      } elseif (in_array($jo->service_type_id,[6,7])) {
        $man=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
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
                              'pieces.name as piece_name')
                            ->first();
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
      // dd($wo->quotation->bill_type);
      if (isset($wo->quotation_detail_id) || isset($wo->price_list_id)) {
        // jika quotation
        $q=QuotationDetail::where('id', $wo->quotation_detail_id)->get();
        foreach ($q as $key => $value) {
          $data[]=[
            'job_order_id' => null,
            'job_order_detail_id' => null,
            'no_po_customer' => '-',
            'trayek' => $value->route->name??'-',
            'commodity' => $value->vehicle_type->name??'-',
            'cost_type_id' => null,
            'work_order_id' => $id,
            'price' => $value->price_contract_full,
            'total_price' => $value->price_contract_full,
            'imposition' => 1,
            'qty' => 1,
            'description' => $value->description??'-',
            'is_other_cost' => 0,
            'type_other_cost' => 0,
            'manifest_id' => null,
            'imposition_name' => $value->piece_name??'',
            'vehicle_type_name' => $value->vehicle_type->name??'-',
            'code' => $wo->code,
            'driver' => '-',
            'nopol' => '-',
            'container_no' => '-',
          ];
        }
        // jika price list
        $q=PriceList::where('id', $wo->price_list_id)->get();
        foreach ($q as $key => $value) {
          $data[]=[
            'job_order_id' => null,
            'job_order_detail_id' => null,
            'no_po_customer' => '-',
            'trayek' => $value->route->name??'-',
            'commodity' => $value->commodity->name??'-',
            'cost_type_id' => null,
            'work_order_id' => $id,
            'price' => $value->price_full,
            'total_price' => $value->price_full,
            'imposition' => 1,
            'qty' => 1,
            'description' => $value->description??'-',
            'is_other_cost' => 0,
            'type_other_cost' => 0,
            'manifest_id' => null,
            'imposition_name' => 'Unit',
            'vehicle_type_name' => $value->vehicle_type->name??'-',
            'code' => $wo->code,
            'driver' => '-',
            'nopol' => '-',
            'container_no' => '-',
          ];
        }
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
      }
      if (($wo->quotation->bill_type??null)==2) {
        // jika quotation borongan
        $stta=$wo->quotation;
        $data[]=[
          'job_order_id' => null,
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
        // dd($data);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
      }
      $wr="1=1";
      if ($request->jo_list_append) {
        $wr.=" AND job_orders.id NOT IN ($request->jo_list_append)";
      }
      $jor=JobOrder::whereRaw("work_order_id = $id and invoice_id is null and service_type_id != 4 and $wr")->get();
      // dd($jor);
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
          	vehicle_types.name as vname
          FROM
          	manifests
            LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
          	LEFT JOIN job_order_details ON manifest_details.job_order_detail_id = job_order_details.id
          	LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
          	LEFT JOIN contacts ON contacts.id = manifests.driver_id
          	LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
          WHERE
          	manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $jo->id )
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
              'commodity' => $jo->commodity->name??'-',
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
        } else if ($jo->service_type_id==1) {
          // $detail=JobOrderDetail::where('header_id', $jo->id)->get();
          $stt=[
            1 => 'Kubikasi',
            2 => 'Tonase',
            3 => 'Item',
          ];
          // $sql="
          // SELECT
          // 	manifests.id as manifest_id,
          // 	manifest_details.job_order_detail_id,
          // 	manifest_details.id as manifest_detail_id,
          // 	job_order_details.header_id as job_order_id,
          // 	manifests.CODE as codes,
          // 	contacts.NAME AS driver,
          // 	vehicles.nopol,
          // 	vehicle_types.NAME AS vname,
          // 	(CASE job_order_details.imposition
          // 	WHEN 1 THEN
          // 		job_order_details.volume/(job_order_details.transported+job_order_details.leftover)*manifest_details.transported
          // 	WHEN 2 THEN
          // 		job_order_details.weight/(job_order_details.transported+job_order_details.leftover)*manifest_details.transported
          // 	ELSE
          // 		manifest_details.transported
          // 	END) as qty,
          // 	job_order_details.price,
          //   containers.container_no,
          //   job_order_details.imposition,
          //   job_order_details.item_name
          // FROM
          // 	manifests
          // 	LEFT JOIN containers ON manifests.container_id = containers.id
          // 	LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
          // 	LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
          // 	LEFT JOIN contacts ON contacts.id = manifests.driver_id
          // 	LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
          // 	LEFT JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id
          // 	LEFT JOIN job_orders ON job_orders.id = manifest_details.header_id
          // WHERE
          // 	manifest_details.job_order_detail_id IN (SELECT * FROM ( SELECT job_order_details.id FROM job_order_details LEFT JOIN job_orders on job_orders.id = job_order_details.header_id WHERE job_orders.work_order_id = $id ) as XS )
          // ";

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
          jo.description
          from job_orders as jo
          left join routes on jo.route_id = routes.id
          left join (select sum(if(imposition=1,volume,if(imposition=2,weight,qty))) as qty,header_id,imposition,sum(total_price) as total_price,max(price) as price from job_order_details group by header_id) Y on Y.header_id = jo.id
          where jo.work_order_id = $id and jo.invoice_id is null";
          $man=DB::select($sql2);
          // dd($man);
          foreach ($man as $mas) {
            $data[]=[
              'job_order_id' => $mas->jo_id,
              'no_po_customer' => $mas->no_po_customer,
              'job_order_detail_id' => null,
              'trayek' => $mas->trayek,
              'commodity' => $mas->item_name,
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
        } elseif (in_array($jo->service_type_id,[6,7])) {
          $mas=JobOrderDetail::leftJoin('job_orders','job_orders.id','=','job_order_details.header_id')
                              ->leftJoin('pieces','pieces.id','=','job_orders.piece_id')
                              ->where("job_order_details.header_id", $jo->id)
                              ->select(
                                'job_orders.code',
                                'job_orders.no_po_customer',
                                'job_orders.id',
                                'job_orders.description',
                                'job_orders.total_price',
                                'job_orders.price',
                                'job_order_details.id as job_order_detail_id',
                                'job_order_details.qty',
                                'job_order_details.item_name',
                                'pieces.name as piece_name')
                              ->first();
                              // dd($man);
          $data[]=[
            'job_order_id' => $mas->id,
            'job_order_detail_id' => $mas->job_order_detail_id,
            'no_po_customer' => $mas->no_po_customer,
            'trayek' => '-',
            'commodity' => $mas->item_name,
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
      // dd($data);
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

    public function posting(Request $request, $id)
    {
      $request->validate([
        'journal_date' => 'required'
      ]);
      
      DB::beginTransaction();
      $in=Invoice::find($id);

      $j=Journal::create([
        'company_id' => $in->company_id,
        'type_transaction_id' => 26, //invoice
        'relation_id' => $in->id,
        'date_transaction' => Carbon::parse($request->journal_date),
        'created_by' => auth()->id(),
        'code' => $in->code,
        'description' => 'Pendapatan atas Invoice No. '.$in->code
      ]);

      $sql_invoice_jo="
      SELECT
      	services.account_sale_id,
      	services.id as service_id,
      	services.name,
        job_orders.code,
      	sum( invoice_details.total_price ) AS total
      FROM
      	invoice_details
      	LEFT JOIN job_orders ON job_orders.id = invoice_details.job_order_id
      	LEFT JOIN services ON services.id = job_orders.service_id
      WHERE invoice_details.header_id = $id and invoice_details.job_order_id is not null
      GROUP BY
      	invoice_details.job_order_id
      ";
      $sql_invoice_ct="
      SELECT
      	cost_types.akun_biaya,
      	cost_types.name,
      	sum( invoice_details.total_price ) AS total
      FROM
      	invoice_details
      	LEFT JOIN cost_types ON cost_types.id = invoice_details.cost_type_id
      WHERE
      	invoice_details.header_id = $id
      	AND invoice_details.cost_type_id IS NOT NULL
      GROUP BY
      	invoice_details.cost_type_id
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
      $diskon=InvoiceDetail::where('header_id', $id)->sum('discount');
      $ppn=InvoiceDetail::where('header_id', $id)->sum('ppn');
      $invoice_jo=DB::select($sql_invoice_jo);
      $invoice_ct=DB::select($sql_invoice_ct);
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

        ReceivableDetail::create([
          'header_id' => $i->id,
          'journal_id' => $j->id,
          'type_transaction_id' => 26,
          'relation_id' => $in->id,
          'code' => $in->code,
          'date_transaction' => Carbon::parse($request->journal_date),
        ]);
      }
      $j->update(['status' => 2]);
      $is_error=false;
      $total_in_jo=0;
      
      foreach ($invoice_jo as $key => $value) {
        if (empty($value->account_sale_id)) {
          $err_message="- Akun pendapatan untuk layanan $value->name belum ditentukan!<br>";
          $is_error=true;
          continue;
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value->account_sale_id,
          'credit' => $value->total,
          'description' => 'Pendapatan Invoice '.$in->code.' untuk JO '.$value->code.' layanan '.$value->name,
        ]);
        if ($in->type_bayar==1) {
          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $value->account_sale_id,
            'contact_id' => $in->customer_id,
            'amount' => $value->total,
            'description' => 'Pendapatan Invoice '.$in->code.' untuk JO '.$value->code.' layanan '.$value->name,
          ]);
        } else {
          ReceivableDetail::whereRaw("header_id = $i->id")->update([
            'debet' => DB::raw('debet+'.$value->total)
          ]);
        }
        $total_in_jo+=$value->total;
      }
      // dd($total_in_jo);
      $total_in_ct=0;
      foreach ($invoice_ct as $key => $value) {
        if (empty($value->akun_biaya)) {
          $err_message="- Akun Biaya untuk jenis biaya $value->name belum ditentukan!<br>";
          $is_error=true;
          continue;
        }
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value->akun_biaya,
          'credit' => $value->total,
          'description' => 'Pendapatan Invoice '.$in->code.' untuk jenis biaya '.$value->name,
        ]);
        if ($in->type_bayar==1) {
          CashTransactionDetail::create([
            'header_id' => $i->id,
            'account_id' => $value->akun_biaya,
            'contact_id' => $in->customer_id,
            'amount' => $value->total,
            'description' => 'Pendapatan Invoice '.$in->code.' untuk jenis biaya '.$value->name,
          ]);
        } else {
          ReceivableDetail::whereRaw("header_id = $i->id")->update([
            'debet' => DB::raw('debet+'.$value->total)
          ]);
        }

        $total_in_ct+=$value->total;
      }

      $total_taxes=0;
      foreach ($tax as $key => $value) {
        if (empty($value->akun_penjualan)) {
          $err_message="- Akun Penjualan untuk pajak $value->name belum ditentukan!<br>";
          $is_error=true;
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
          $err_message="- Akun default PPN Keluaran belum ditentukan!<br>";
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
          'account_id' => $in->account_receivable_id,
          'debet' => $in->grand_total,
          'description' => 'Pendapatan atas Invoice '.$in->code,
        ]);
      }

      //jurnal biaya ----------------------------------------------<<

      // $sql_cost="
      // SELECT
      // 	SUM(job_order_costs.total_price) as total,
      // 	CONCAT(cost_types.name,' - No. JO',job_orders.code) as name,
      // 	cost_types.akun_biaya,
      // 	cost_types.akun_uang_muka
      // FROM
      // 	job_order_costs
      // 	LEFT JOIN job_orders ON job_orders.id = job_order_costs.header_id
      // 	LEFT JOIN invoice_details on job_orders.id = invoice_details.job_order_id
      // 	LEFT JOIN cost_types on job_order_costs.header_id = cost_types.id
      // 	WHERE invoice_details.header_id = $id
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

      $in->update([
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
        $invoice = Invoice::find($id);
        
        DB::beginTransaction();
      
        $invoice->update([
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
      if ($format==1) {
        $response=$this->print_format_1($id);
      } else if ($format==2) {
        $response=$this->print_format_2($id);
      } else if ($format==3) {
        $response=$this->print_format_3($id);
      } else if ($format==4) {
        $response=$this->print_format_4($id);
      } else if ($format==5) {
        $response=$this->print_format_5($id);
      }
      return $response;
    }
    public function print_format_1($id)
    {
      $data['item']=Invoice::find($id);
      $data['details']=InvoiceDetail::where('header_id', $id)
      ->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id')
      ->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id')
      ->selectRaw('
        invoice_details.*,
        min(invoice_details.code) as code_inv,
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
      return PDF::loadView('pdf.invoice.wo-gabungan', $data)->stream();
    }
    public function print_format_2($id,$is_gabungan=0)
    {
      $data['item']=Invoice::find($id);
      $data['addon']=DB::select("select group_concat(distinct job_orders.aju_number) as aju,group_concat(distinct job_orders.no_bl) as bl, GROUP_CONCAT(distinct work_orders.code) as code_wo from invoice_details left join job_orders on job_orders.id = invoice_details.job_order_id left join work_orders on work_orders.id = job_orders.work_order_id where invoice_details.header_id = $id")[0];
      $wolist=DB::table('invoice_details')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->where('header_id', $id)
      ->where('work_orders.id','!=',null)
      ->selectRaw('job_orders.work_order_id,work_orders.code as wo_code')
      ->groupBy('job_orders.work_order_id')
      ->get();
      // dd($wolist);
      $data['details']=[];
      foreach ($wolist as $key => $value) {
        // dd($value);
        if (!$value->work_order_id) {
          continue;
        }
        $trucking=DB::table('invoice_details')
        ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('routes','routes.id','job_orders.route_id')
        ->leftJoin('container_types','container_types.id','job_orders.container_type_id')
        ->leftJoin('vehicle_types','vehicle_types.id','job_orders.vehicle_type_id')
        ->whereRaw("job_orders.service_type_id in (1,2,3) and header_id = $id and job_orders.work_order_id = $value->work_order_id and invoice_details.cost_type_id is null")
        ->selectRaw("sum(invoice_details.total_price) as total_price, sum(invoice_details.qty) as qty,
        services.name as service,routes.name as trayek, sum(invoice_details.ppn) as ppn, concat(container_types.code) as ctype, vehicle_types.name as vtype")
        ->groupBy('job_orders.service_id','job_orders.route_id','job_orders.container_type_id','job_orders.vehicle_type_id')
        ->orderBy('services.service_type_id','asc')
        ->get();
        $ff=DB::table('invoice_details')
        ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('pieces','pieces.id','job_orders.piece_id')
        ->whereRaw("job_orders.service_type_id not in (1,2,3) and header_id = $id and job_orders.work_order_id = $value->work_order_id and invoice_details.cost_type_id is null")
        ->selectRaw("
        invoice_details.price as price,
        sum(invoice_details.total_price) as total_price,
        sum(invoice_details.qty) as qty,services.name as service,
        sum(invoice_details.ppn) as ppn,
        sum(invoice_details.discount) as discount,
        pieces.name as piece
        ")
        ->groupBy('services.id','job_orders.piece_id')
        ->orderBy('services.service_type_id','asc')
        ->orderBy('services.id','desc')
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
      return PDF::loadView('pdf.invoice.wo-persatuan', $data)->stream();
    }
    public function print_format_3($id)
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
        return PDF::loadView('pdf.invoice.inter-island-1', $data)->stream();
      }else {
        return PDF::loadView('pdf.invoice.inter-island-2', $data)->stream();
      }
    }
    public function print_format_4($id,$is_gabungan=0)
    {
      $data['item']=Invoice::find($id);
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
      ->whereRaw("invoice_details.header_id = $id and invoice_details.job_order_id is not null")
      ->selectRaw('
      sum(if(job_orders.service_type_id=1,job_orders.total_unit,invoice_details.qty)) as qty,
      sum(invoice_details.total_price) as total_price,
      sum(invoice_details.ppn) as ppn,
      if(job_orders.service_type_id=1,sum(invoice_details.total_price),invoice_details.price) as price,
      vehicle_types.name as vname,
      container_types.code as cname,
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as name,
      CONCAT(services.name,"<br>",routes.name) as trayek,
      city_start.name as city_start,
      city_end.name as city_end,
      group_concat(distinct work_orders.code SEPARATOR \'<br>\') as code_wo
      ')
      ->groupBy('manifests.vehicle_type_id','manifests.container_type_id')
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
      ->leftJoin('manifests','manifests.id','invoice_details.manifest_id')
      ->leftJoin('delivery_order_drivers','manifests.id','delivery_order_drivers.manifest_id')
      ->leftJoin('job_orders','job_orders.id','invoice_details.job_order_id')
      ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->leftJoin('cities as city_start','city_start.id','routes.city_from')
      ->leftJoin('cities as city_end','city_end.id','routes.city_to')
      ->leftJoin('container_types','container_types.id','manifests.container_type_id')
      ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
      ->leftJoin('vehicles','vehicles.id','manifests.vehicle_id')
      ->whereRaw("invoice_details.header_id = $id and invoice_details.manifest_id is not null")
      ->selectRaw('
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as name,
      invoice_details.price,
      (select GROUP_CONCAT(jbdr.item_name) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as item_name,
      (select MAX(jbdr.weight) from manifest_details as mds left join job_order_details as jbdr on jbdr.id = mds.job_order_detail_id where mds.header_id = manifests.id) as tonase,
      (select if(SUM(mds.transported)=0,1,SUM(mds.transported)) from manifest_details as mds where mds.header_id = manifests.id) as qty,
      manifests.driver,
      group_concat(distinct job_orders.no_po_customer) as po_customer,
      delivery_order_drivers.code as no_sj,
      city_start.name as city_start,
      city_end.name as city_end,
      IF(vehicles.id is not null,vehicles.nopol,manifests.nopol) as nopol,
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

      return PDF::loadView('pdf.invoice.trucking2', $data)->stream();
    }
    public function print_format_5($id)
    {
      $data['item']=Invoice::find($id);
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

      return PDF::loadView('pdf.invoice.project', $data)->stream();
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
      $data=DB::table('invoices')
      ->leftJoin(DB::raw('(select invoice_details.header_id, group_concat(distinct jo.aju_number) as aju, group_concat(distinct jo.no_bl) as bl, group_concat(distinct jo.no_po_customer) as po_customer from invoice_details left join job_orders as jo on jo.id = invoice_details.job_order_id group by invoice_details.header_id) as Y'),'Y.header_id','invoices.id')
      ->whereRaw("invoices.id in ($request->invoice_list)")
      ->selectRaw("
      id,
      code,
      Y.aju,
      Y.bl,
      Y.po_customer,
      grand_total,
      date_invoice
      ")
      ->get();
      return Response::json($data,200);
    }

    public function print_wo_gabungan(Request $request)
    {
      // dd($request);
      $data['item']=Invoice::whereRaw("id in ($request->list)")->first();
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
      if ($request->type_wo==1) {
        foreach ($data['details'] as $key => $value) {
          $data['lampiran'][$key]=$this->print_format_2($value->header_id,1);
        }
      } else {
        foreach ($data['details'] as $key => $value) {
          $data['lampiran'][$key]=$this->print_format_4($value->header_id,1);
        }
      }
      // dd($data);

      return PDF::loadView('pdf.invoice.wo-gabungan', $data)->stream();
    }

    public function jo_list(Request $request)
    {
      $data=DB::table('job_orders')
      ->leftJoin('services','services.id','job_orders.service_id')
      ->leftJoin('routes','routes.id','job_orders.route_id')
      ->where('customer_id', $request->customer_id)
      ->selectRaw("
      job_orders.id,
      concat(job_orders.code,' / ',services.name,' ',ifnull(routes.name,'')) as name,
      job_orders.code
      ")
      ->orderBy('job_orders.code','desc')->get();

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

}

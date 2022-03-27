<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\JournalDetail;
use App\Model\Manifest;
use App\Model\ManifestDetail;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\ManifestCost;
use App\Model\CostType;
use App\Model\Contact;
use App\Model\Company;
use App\Model\VehicleContact;
use App\Model\VehicleType;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\DeliveryOrderDriver;
use App\Model\SubmissionCost;
use App\Model\Vehicle;
use App\Model\JobStatusHistory;
use App\Model\Route as Trayek;
use App\Utils\TransactionCode;
use App\Abstracts\ManifestCost AS MC;
use DB;
use Response;
use Carbon\Carbon;
use PDF;
use Auth;
use App\Jobs\HitungJoCostManifestJob;
use App\Abstracts\Operational\Manifest AS M;
use App\Abstracts\Operational\ManifestDetail AS MD;
use App\Abstracts\Operational\DeliveryOrderStatusLog;
use App\Abstracts\JobOrderDetail AS JOD;
use App\Abstracts\Inventory\PickingDetail;
use App\Abstracts\Sales\SalesOrderDetail;
use Exception;

class ManifestFTLController extends Controller
{
    protected $source = ['job_order' => 'Job Order', 'picking_order' => 'Picking Order', 'sales_order' => 'Sales Order'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function indexSource() {
        $data['message'] = 'OK';
        $data['data'] = $this->source;

        return response()->json($data);
    }


    public function ajukan_atasan(Request $request)
    {
      DB::beginTransaction();
      ManifestCost::find($request->id)->update([
        'status' => 7
      ]);
      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function approve_atasan(Request $request)
    {
      DB::beginTransaction();
      ManifestCost::find($request->id)->update([
        'status' => 8,
        'approve_by' => auth()->id()
      ]);

      DB::commit();
      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function reject_atasan(Request $request)
    {
      DB::beginTransaction();
      ManifestCost::find($request->id)->update([
        'status' => 4
      ]);

      DB::commit();
      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }



    public function cost_journal(Request $request)
    {
      DB::beginTransaction();


      // Hitung biaya job order
      $jumlah_jo = 0;

     $manifest_details = DB::table('manifests')
     ->join('manifest_details', 'manifest_details.header_id', 'manifests.id')
     ->join('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id')
     ->join('job_orders', 'job_orders.id', 'job_order_details.header_id')
     ->where('manifests.id', $request->id)
     ->select('job_orders.total_price', 'job_orders.id as job_order_id')
     ->groupBy('job_orders.id')
     ->get();

     foreach($manifest_details as $detail) {
        $jumlah_jo += $detail->total_price;
     }
     $manifest_costs = DB::table('manifest_costs')
     ->whereStatus(8)
     ->whereHeaderId($request->id)
     ->get();

    foreach($manifest_details as $detail) {
        $cost_total = 0;
        foreach($manifest_costs as $x => $cost) {
            $biaya = $cost->price;
            if($jumlah_jo > 0) {
                if($x == count($manifest_costs)) {
                    $biaya -= $cost_total;
                }
                $biaya = $detail->total_price * $cost->price / $jumlah_jo;
                $cost_total += $biaya;
            }
            $check = DB::table('job_order_costs')->where('manifest_cost_id', $cost->id)->where('header_id', $detail->job_order_id)->count();
            if ($check>0) continue;
            $total_biaya = $biaya * $cost->qty;

            DB::table('job_order_costs')->insert([
              'header_id' => $detail->job_order_id,
              'cost_type_id' => $cost->cost_type_id,
              'transaction_type_id' => 21,
              'vendor_id' => $cost->vendor_id,
              'qty' => $cost->qty,
              'price' => $biaya,
              'total_price' => $total_biaya,
              'description' => $cost->description,
              'create_by' => auth()->id(),
              'type' => 1,
              'slug' => $cost->slug,
              'is_edit' => 1,
              'status' => 5,
              'is_invoice' => 1,
              'manifest_cost_id' => $cost->id
            ]);
        }
    }


      $jo=DB::table('manifests')->where('id', $request->id)->first();
      $cst=DB::table('manifest_costs')
      ->leftJoin('cost_types','cost_types.id','manifest_costs.cost_type_id')
      ->where('header_id', $request->id)
      ->where('status', 8)
      ->where('cost_types.type', 1)
      ->selectRaw('
        manifest_costs.id,
        manifest_costs.cost_type_id,
        manifest_costs.vendor_id,
        manifest_costs.total_price,
        cost_types.name,
        cost_types.type,
        cost_types.akun_biaya,
        cost_types.akun_kas_hutang,
        cost_types.akun_uang_muka
      ')
      ->get();
      if (count($cst) > 0) {
          $hutang=0;
          foreach ($cst as $value) {
            $j=Journal::create([
              'company_id' => $jo->company_id,
              'type_transaction_id' => 54,
              'date_transaction' => Carbon::now(),
              'created_by' => auth()->id(),
              'code' => $jo->code,
              'status' => 2,
              'description' => "Biaya Manifest - $jo->code - {$value->name}",
              'debet' => 0,
              'credit' => 0,
            ]);
            JournalDetail::create([
              'header_id' => $j->id,
              'account_id' => $value->akun_biaya,
              'debet' => $value->total_price,
              'credit' => 0,
              'description' => "Biaya Manifest - $value->name"
            ]);
            JournalDetail::create([
              'header_id' => $j->id,
              'account_id' => $value->akun_uang_muka,
              'debet' => 0,
              'credit' => $value->total_price,
              'description' => "Biaya Manifest - $value->name"
            ]);
            $joc = ManifestCost::find($value->id);
            $joc->update([
              'status' => 5,
              'journal_id' => $j->id
            ]);
            $contact_id = DB::table('cost_types')
            ->whereId($joc->cost_type_id)
            ->first()
            ->vendor_id ?? null;
            // $p=Payable::create([
            //     'company_id' => $jo->company_id,
            //     'contact_id' => $joc->vendor_id ?? $contact_id,
            //     'type_transaction_id' => 54,
            //     'journal_id' => $j->id,
            //     'relation_id' => $value->id,
            //     'created_by' => Auth::id(),
            //     'code' => $jo->code,
            //     'date_transaction' => Carbon::now(),
            //     'date_tempo' => Carbon::now(),
            //     'description' => "Biaya Manifest - $jo->code - $value->name",
            //     'is_invoice' => 0
            //   ]);
            //   PayableDetail::create([
            //     'header_id' => $p->id,
            //     'journal_id' => $j->id,
            //     'type_transaction_id' => 54,
            //     'relation_id' => $value->id,
            //     'code' => $jo->code,
            //     'date_transaction' => Carbon::now(),
            //     'credit' => $value->total_price,
            //     'description' => "Biaya Manifest - $jo->code - $value->name",
            //     'is_journal' => 1
            //   ]);
          }
      }

      $cst_cash=DB::table('manifest_costs')
      ->leftJoin('cost_types','cost_types.id','manifest_costs.cost_type_id')
      ->where('header_id', $request->id)
      ->where('status', 8)
      ->where('cost_types.type', 2)
      ->selectRaw('
        manifest_costs.id,
        manifest_costs.cost_type_id,
        manifest_costs.vendor_id,
        manifest_costs.total_price,
        manifest_costs.created_at,
        manifest_costs.description,
        cost_types.name,
        cost_types.type,
        cost_types.akun_biaya,
        cost_types.akun_kas_hutang,
        cost_types.akun_uang_muka
      ')
      ->get();

      if(count($cst) == 0 && count($cst_cash) == 0) {

           return Response::json(['message' => 'Tidak ada biaya packing list yang disetujui atasan!'],500,[],JSON_NUMERIC_CHECK);
      }



      foreach ($cst_cash as $value) {
          $m = ManifestCost::find($value->id);
          $m->update([
            'status' => 5
          ]);
          $tp = DB::table('type_transactions')->where('slug', 'biayaManifest')->first();

          $account = DB::table('accounts')
          ->whereId($value->akun_kas_hutang)
          ->first();
          if($account->no_cash_bank != 0) {
              $i=CashTransaction::create([
                    'company_id' => $jo->company_id,
                    'type_transaction_id' => $tp->id,
                    'code' => $jo->code,
                    'reff' => $jo->code,
                    'jenis' => 2,
                    'type' => $account->no_cash_bank,
                    'description' => "Biaya Manifest - $jo->code - {$value->name}",
                    'total' => $value->total_price,
                    'account_id' => $value->akun_kas_hutang,
                    'date_transaction' => dateDB($value->created_at),
                    'status_cost' => 1,
                    'created_by' => auth()->id()
                ]);

              CashTransactionDetail::create([
                  'header_id' => $i->id,
                  'account_id' => $value->akun_biaya,
                  'amount' => $value->total_price,
                  'description' => "Biaya Manifest - $jo->code - {$value->name}",
                  'manifest_cost_id' => $value->id,
                  'jenis' => 1
              ]);
          } else {
              $jurnal=[
                  'company_id' => $jo->company_id,
                  'date_transaction' => date('Y-m-d'),
                  'created_by' => auth()->id(),
                  'code' => $jo->code,
                  'description' => "Biaya Manifest - $jo->code - {$value->name}",
                  'debet' => 0,
                  'credit' => 0,
                  'status' => 2,
                  'type_transaction_id' => $tp->id
              ];

              $j = Journal::create($jurnal);

              JournalDetail::create([
                  'header_id' => $j->id,
                  'account_id' => $value->akun_biaya,
                  'debet' => $value->total_price,
                  'credit' => 0,
                  'description' => "Biaya Manifest - $jo->code - {$value->name}",
              ]);

              JournalDetail::create([
                  'header_id' => $j->id,
                  'account_id' => $value->akun_kas_hutang,
                  'debet' => 0,
                  'credit' => $value->total_price,
                  'description' => "Biaya Manifest - $jo->code - {$value->name}"
              ]);
          }
          $m->update([
            'journal_id' => $j->id ?? null
          ]);

      }

      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function cancel_cost_journal($cost_id)
    {
      DB::beginTransaction();
      $joc = DB::table('manifest_costs')->where('id', $cost_id)->first();
      if ($joc->journal_id) {
        DB::delete("DELETE p, pd FROM payables as p LEFT JOIN payable_details as pd ON pd.header_id = p.id WHERE p.journal_id = {$joc->journal_id}");
        DB::delete("DELETE j FROM journals as j WHERE j.id = {$joc->journal_id}");
      }
      DB::delete("DELETE ct, ctd FROM cash_transactions as ct LEFT JOIN cash_transaction_details as ctd ON ctd.header_id = ct.id WHERE ct.relation_id = {$cost_id}");
      DB::update("UPDATE manifest_costs SET journal_id = null, status = 8 WHERE id = {$cost_id}");
      DB::commit();

      return response()->json(['message' => 'OK']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['customer']=Contact::where('is_pelanggan', 1)->select('id','name')->get();
      $data['vehicle']=Vehicle::select('id','nopol')->get();
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
            'company_id' => 'required',
            'date_manifest' => 'required',
            'route_id' => 'required',
            'vehicle_type_id' => 'required',
            'driver_id' => 'required'
        ]);
        DB::beginTransaction();
        $code = new TransactionCode($request->company_id, 'manifest');
        $code->setCode();
        $trx_code = $code->getCode();

        $i=Manifest::create([
            'source' => $request->source ?? 'job_order',
            'is_crossdocking' => $request->is_crossdocking ?? 0,
            'company_id' => $request->company_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'route_id' => $request->route_id,
            'transaction_type_id' => 22,
            'vehicle_id' => $request->vehicle_id,
            'reff_no' => $request->reff_no,
            'code' => $trx_code,
            'driver_id' => $request->driver_id,
            'create_by' => auth()->id(),
            'date_manifest' => Carbon::parse($request->date_manifest),
            'is_container' => 0,
            'is_full' => $request->is_full,
            'description' => $request->description,
            'etd_time' => createTimestamp($request->etd_date,$request->etd_time),
            'depart' => createTimestamp($request->etd_date,$request->etd_time),
            'eta_time' => createTimestamp($request->eta_date,$request->eta_time),
            'arrive' => createTimestamp($request->eta_date,$request->eta_time),
        ]);
        $manifest_id = $i->id;
        if($request->filled('driver_id') && $request->filled('vehicle_id')) {
            $params = [];
            $params['is_internal'] = 1;
            $params['commodity_name'] = '-';
            $params['vehicle_internal_id'] = $request->vehicle_id;
            $params['driver_internal_id'] = $request->driver_id;
            $this->store_delivery(new Request($params), $i->id);
        }

        if($request->sales_order_id) {
            $salesOrderDetails = SalesOrderDetail::index($request->sales_order_id);
            $salesOrderDetails = $salesOrderDetails->map(function($v){
                $r = [];
                $r['id'] = $v->job_order_detail_id;
                $r['pickup'] = $v->qty;

                return $r;
            });
            $args = [];
            $args['detail'] = $salesOrderDetails;
            $this->add_item(new Request($args), $manifest_id);
        }


        DB::commit();
        $data['message'] = 'Data successfully saved';
        $data['id'] = $i->id;

        return Response::json($data);
    }

    public function storeAdditional(Request $request, $id) {
        DB::beginTransaction();
        $dt['message'] = 'Data successfully saved';
        $status_code = 200;
        try {
            $params = $request->all();
            M::storeAdditional($params, $id);
        } catch (Exception $e) {
            $status_code = 421;
            $dt['message'] = $e->getMessage();
        }
        DB::commit();

        return Response::json($dt, $status_code);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      M::setNullableAdditional($id);
      $data['item']=M::show($id);
      $data['detail']=MD::index($id);
      $data['cost']=ManifestCost::with('cost_type','vendor')->where('header_id', $id)->get();
      $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id',$data['item']->company_id)->where('parent_id','!=',null)->get();
      $data['vehicle_internal']=DB::table('vehicles')->where('is_internal', 1)->selectRaw('id,nopol')->get();
      $data['vehicle_eksternal']=DB::table('vehicles')->where('is_internal', 0)->selectRaw('id,nopol,supplier_id as vendor_id')->get();
      $cancellations = DeliveryOrderDriver::with('rejected_by','vehicle','driver')
        ->where('manifest_id', $id)
        ->where('status', '>', 2)->get();
      $data['cancellation'] = [];

      foreach($cancellations as $cancellation) {
          $data['cancellation'] []= [
            'code' => $cancellation->code,
            'nama_driver' => ($cancellation->is_internal) ? $cancellation->driver->name : $cancellation->driver_name,
            'nopol_kendaraan' => ($cancellation->is_internal) ? $cancellation->vehicle->nopol : $cancellation->nopol,
            'cancelled_by' => $cancellation->rejected_by->name,
            'cancel_reason' => $cancellation->cancel_reason
          ];
      }

      $data['delivery_order']=DB::table('delivery_order_drivers as dod')
      ->leftJoin('contacts as driver','driver.id','dod.driver_id')
      ->leftJoin('contacts as vendor','vendor.id','driver.parent_id')
      ->leftJoin('vehicles','vehicles.id','dod.vehicle_id')
      ->leftJoin('job_statuses','job_statuses.id','dod.job_status_id')
      ->where('dod.manifest_id', $id)
      ->where('dod.status', '<', 3)
      ->selectRaw('
      dod.id,
      dod.code as code_sj,
      dod.is_internal,
      dod.vehicle_id,
      dod.driver_id,
      dod.vendor_id,
      job_statuses.name as status,
      vendor.name as vendor_name,
      if(dod.driver_id is not null,driver.name, dod.driver_name) as sopir,
      if(dod.vehicle_id is not null,vehicles.nopol, dod.nopol) as kendaraan')->get();
      // dd($data);
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function show_cost($id)
    {
      $data['cost_detail']=ManifestCost::with('cost_type:id,name', 'vendor:id,name')
      ->whereHeaderId($id)
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            M::destroy($id);
        } catch (Exception $e) {
            return Response::json(['message' => $e->getMessage()], 421);
        }
        return Response::json(['message' => 'Data successfully removed']);
    }

    public function add_cost(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'cost_type' => 'required',
        'is_internal' => 'required',
        'vendor_id' => 'required_if:is_internal,0',
        'qty' => 'required|integer|min:1',
        'total_price' => 'required|integer|min:1',
      ]);
      DB::beginTransaction();
      $jo=Manifest::find($id);
      if($request->is_edit) {
        ManifestCost::find($request->id)->update([
          'cost_type_id' => $request->cost_type,
          'vendor_id' => $request->vendor_id,
          'qty' => $request->qty,
          'price' => $request->price,
          'total_price' => $request->total_price,
          'description' => $request->description,
          'type' => $request->type,
          'is_edit' => 1
        ]);
      } else {

          $m = ManifestCost::create([
            'header_id' => $id,
            'company_id' => $jo->company_id,
            'cost_type_id' => $request->cost_type,
            'transaction_type_id' => 21,
            'vendor_id' => $request->vendor_id,
            'qty' => $request->qty,
            'price' => $request->price,
            'total_price' => $request->total_price,
            'description' => $request->description,
            'is_internal' => $request->is_internal,
            'is_generated' => 1,
            'type' => $request->type,
            'create_by' => auth()->id(),
            'is_edit' => 1
          ]);

          MC::storeVendorJob($m->id);
      }
      DB::commit();

      return Response::json(null);
    }

    public function list_customer_manifest()
    {
      $lists=[];
      $data=DB::table('contacts')
      ->leftJoin('job_orders','job_orders.customer_id','contacts.id')
      ->where('job_orders.id','!=',null)
      ->groupBy('contacts.id')
      ->selectRaw('contacts.id,contacts.name')
      ->orderBy('id','asc')
      ->chunk(50, function($chunk) use (&$lists) {
        foreach ($chunk as $value) {
          array_push($lists, [
            'id' => $value->id,
            'name' => $value->name
          ]);
        }
      });
      return response()->json([
        'data' => $lists
      ]);
    }

    public function list_job_order($id, Request $request)
    {
      // $m=DB::table('manifests')->where('id', $id)->selectRaw('id,is_full')->first();
      $company_id = auth()->user()->company_id;
      $detail_array=DB::table('manifest_details')
      ->pluck('id');

      $detail=DB::table('job_order_details as jod')
      ->leftJoin('job_orders as jo','jo.id','jod.header_id')
      ->leftJoin('contacts','contacts.id','jo.customer_id');
      if ($request->customer_id) {
        $detail=$detail->where('jo.customer_id', $request->customer_id);
      }
      $detail=$detail->where('jo.service_type_id', 1);
      $detail=$detail->where('jo.company_id', $company_id);
      $detail=$detail->where('jod.leftover', '>', 0);
      $detail=$detail->orderBy('jod.id','desc');
      $detail=$detail->selectRaw('
        jod.qty,
        jod.transported,
        jod.item_name,
        jod.id,
        jo.code,
        contacts.name as customer
      ')
      ->get();

      return Response::json($detail,200,[],JSON_NUMERIC_CHECK);
    }

    public function list_job_order_bkp($id)
    {
      $m=Manifest::find($id);
      if ($m->is_full==1) {
        $md=ManifestDetail::where('header_id', $id)->count();
        $wr="";
        if ($md>0) {
          // $wr.="AND job_order_details.id IN ( SELECT job_order_detail_id FROM manifest_details WHERE header_id = $id )";
          $wr.="AND job_order_details.header_id IN ( SELECT * FROM ( SELECT job_order_details.header_id FROM manifest_details LEFT JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id WHERE manifest_details.header_id = $id ) as XX )";
        }
        $sql="
        SELECT
        	job_order_details.id,
        	job_orders.code,
        	contacts.name as customer,
        	job_order_details.item_name,
        	job_order_details.qty,
        	job_order_details.transported,
        	job_order_details.leftover
        FROM
        	job_order_details
        	LEFT JOIN manifest_details ON manifest_details.job_order_detail_id = job_order_details.id
        	LEFT JOIN job_orders ON job_order_details.header_id = job_orders.id
        	LEFT JOIN contacts ON job_orders.customer_id = contacts.id
        	LEFT JOIN manifests ON manifest_details.header_id = manifests.id
        WHERE
          1=1
        	$wr
          AND job_order_details.leftover > 0
        GROUP BY job_order_details.id
        ORDER BY id ASC
        ";
        $data=DB::select($sql);
      } else {
        $sql="
        SELECT
        	job_order_details.id,
        	job_orders.code,
        	contacts.NAME AS customer,
        	job_order_details.item_name,
        	job_order_details.qty,
        	job_order_details.transported,
        	job_order_details.leftover
        FROM
        	job_order_details
        	LEFT JOIN manifest_details ON manifest_details.job_order_detail_id = job_order_details.id
        	LEFT JOIN job_orders ON job_order_details.header_id = job_orders.id
        	LEFT JOIN contacts ON job_orders.customer_id = contacts.id
        	LEFT JOIN manifests ON manifest_details.header_id = manifests.id
        WHERE
          job_order_details.leftover > 0
        GROUP BY job_order_details.id
        ORDER BY id ASC
        ";
        $data=DB::select($sql);
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function create_delivery($id)
    {
      $data['item']=Manifest::find($id);
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address')->get();
      $data['vehicle_internal']=DB::table('vehicles')->where('is_internal', 1)->selectRaw('id,nopol')->get();
      $data['vehicle_eksternal']=DB::table('vehicles')->where('is_internal', 0)->selectRaw('id,nopol,supplier_id as vendor_id')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function add_item(Request $request, $id)
    {
        $request->validate([
            'detail' => 'required'
        ]);

        DB::beginTransaction();
        $m=Manifest::find($id);
        $counter=0;

        foreach ($request->detail as $key => $val) {
            $value=(object)$val;
            if(($value->id ?? null)) {
                $sisa = JOD::getAvailableQty($value->id);
            } else {
                if(($value->picking_detail_id ?? null)) {
                    $sisa = PickingDetail::getAvailableQty($value->picking_detail_id);
                } else {
                    $sisa = 9999999999;
                }
            }

            if(!($value->id ?? null)) {
                $value->sisa = 999999999;
            }

            if ($value->pickup<1) {
              continue;
            } elseif ($value->pickup > $sisa) {
              return Response::json(['message' => "Terdapat jumlah item melebihi jumlah sisa yang bisa diangkut!"],500,[],JSON_NUMERIC_CHECK);
            }

            $counter+=$value->pickup;
            if($value->id ?? null) {
                $cek=DB::table('manifest_details')->whereRaw("header_id = {$id} and job_order_detail_id = {$value->id}")->first();
                if ($cek) {
                  ManifestDetail::find($cek->id)->update([
                    'requested_qty' => DB::raw("requested_qty+{$value->pickup}"),
                    'transported' => DB::raw("transported+{$value->pickup}"),
                    'update_by' => auth()->id()
                  ]);
                  continue;
                }
            }

            ManifestDetail::create([
              'header_id' => $id,
              'job_order_detail_id' => $value->id ?? null,
              'picking_detail_id' => $value->picking_detail_id ?? null,
              'requested_qty' => $value->pickup,
              'transported' => 0,
              'discharged_qty' => 0,
              'create_by' => auth()->id(),
              'update_by' => auth()->id(),
            ]);

            if(($value->id ?? null)) {
                DB::table('job_order_details')->where('id', $value->id)->update([
                  'transported' => DB::raw('transported+'.$value->pickup),
                  'leftover' => DB::raw('leftover-'.$value->pickup),
                ]);
            }
        }

        if ($counter<1) {
            return Response::json(['message' => 'Jumlah item barang kosong!'],500,[],JSON_NUMERIC_CHECK);
        }
        DB::commit();
        HitungJoCostManifestJob::dispatch($id);
        return Response::json(['message' => 'Data sucessfully saved']);
    }

    public function cari_kendaraan($id)
    {
      $data=VehicleContact::with('vehicle')->where('contact_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 09-04-2020
      Description : Menyalin biaya ritase ke packing list
      Developer : Didin
      Status : Create
    */
    public function setVehicleCost($manifest_id, $vehicle_id) {
        if($vehicle_id != null) {
            $manifest = DB::table('manifests')
            ->whereId($manifest_id)
            ->first();

            if($manifest->is_container == 0) {
                  $vehicle = DB::table('vehicles AS V')
                  ->join('vehicle_variants AS VV', 'VV.id', 'V.vehicle_variant_id')
                  ->where('V.id', $vehicle_id)
                  ->select('VV.vehicle_type_id')
                  ->first();

                  $route_costs = DB::table('route_costs')
                  ->whereVehicleTypeId($vehicle->vehicle_type_id)
                  ->whereRouteId($manifest->route_id)
                  ->get();

                  foreach($route_costs as $route_cost) {
                      $route_cost_details = DB::table('route_cost_details')
                      ->whereHeaderId($route_cost->id)
                      ->get();

                      foreach($route_cost_details as $detail) {
                          $cost_type = DB::table('cost_types')
                          ->whereId($detail->cost_type_id)
                          ->first();
                          DB::table('manifest_costs')
                          ->insert([
                              'header_id' => $manifest_id,
                              'company_id' => $manifest->company_id,
                              'cost_type_id' => $detail->cost_type_id,
                              'vendor_id' => $cost_type->vendor_id ?? null,
                              'transaction_type_id' => 21,
                              'qty' => 1,
                              'price' => $detail->cost,
                              'total_price' => $detail->cost,
                              'total_price' => $detail->cost,
                              'is_internal' => $detail->is_internal,
                              'type' => 1,
                              'create_by' => auth()->id(),
                              'is_edit' => 1
                          ]);
                      }
                  }
            }


        }
    }

    public function store_delivery(Request $request, $id)
    {
      // dd($request);
      $validates=[
        'is_internal' => 'required',
        'vendor_id' => 'required_if:is_internal,0',
        'driver_internal_id' => 'required_if:is_internal,1',
        'vehicle_internal_id' => 'required_if:is_internal,1',
        'delivery_order_number' => 'required_if:is_internal,0',
      ];

      $request->validate($validates,[
        'vendor_id.required_if' => 'Vendor harus dimasukkan jika memilih Eksternal',
        'driver_internal_id.required_if' => 'Driver harus dimasukkan jika memilih Internal',
        'vehicle_internal_id.required_if' => 'No. Polisi harus dimasukkan jika memilih Internal',
        'delivery_order_number.required_if' => 'No Surat Jalan harus dimasukkan jika memilih Eksternal',
      ]);

      DB::beginTransaction();
      $this->setVehicleCost($id, ($request->is_internal == 1 ? $request->vehicle_internal_id : $request->vehicle_eksternal_id));
      $m=Manifest::find($id);

      if ($request->is_internal==1) {
        $code = new TransactionCode($m->company_id, 'deliveryOrderDriver');
        $code->setCode();
        $trx_code = $code->getCode();
      } else {
        $trx_code = $request->delivery_order_number;
      }

      $unit_create = [
        'manifest_id' => $id,
        'from_id' => $request->from_id,
        'from_address_id' => $request->from_address_id,
        'to_id' => $request->to_id,
        'to_address_id' => $request->to_address_id,
        'commodity_name' => $request->commodity_name ?? '-',
        // 'pick_date' => createTimestamp($request->pick_date,$request->pick_time),
        // 'finish_date' => createTimestamp($request->finish_date,$request->finish_time),
        'is_internal' => $request->is_internal,
        'code' => $trx_code,
        'create_by' => auth()->id(),
      ];

      if($request->is_internal == 1) {
        $unit_create['job_status_id'] = 2;
        $unit_create['driver_id'] = $request->driver_internal_id;
        $unit_create['vehicle_id'] = $request->vehicle_internal_id;
        $unit_create['commodity_name'] = DB::table('manifest_details')
        ->join('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id')
        ->where('manifest_details.header_id', $id)
        ->selectRaw('GROUP_CONCAT(job_order_details.item_name SEPARATOR ",") commodity_name')
        ->first()
        ->commodity_name;
        if(!$unit_create['commodity_name']) {
            $unit_create['commodity_name'] = $request->commodity_name ?? '-';
        }

        $unit_update = [
          'is_internal_driver' => 1,
          'vehicle_id' => $request->vehicle_internal_id,
          'driver_id' => $request->driver_internal_id
        ];
      } else {
        $unit_create['vendor_id'] = $request->vendor_id;
        $unit_create['driver_id'] = $request->driver_eksternal_id;
        $unit_create['vehicle_id'] = $request->vehicle_eksternal_id;
        $unit_create['commodity_name'] = DB::table('manifest_details')
        ->join('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id')
        ->where('manifest_details.header_id', $id)
        ->selectRaw('GROUP_CONCAT(job_order_details.item_name SEPARATOR ",") commodity_name')
        ->first()
        ->commodity_name;
        if(!$unit_create['commodity_name']) {
          $unit_create['commodity_name'] = $request->commodity_name ?? '-';
        }

        if ($request->vendor_id && $request->driver_eksternal_id) {
          $unit_create['job_status_id'] = 2;
        }
        if ($request->driver_eksternal_id && !$request->vehicle_eksternal_id) {
          return Response::json(['message' => 'The given data was invalid.','errors' => ['vehicle_eksternal_id' => ['No Polis harus diisi jika driver dipilih!']]],422,[],JSON_NUMERIC_CHECK);
        }
        $unit_update = [
          'is_internal_driver' => 0,
          'vehicle_id' => $request->vehicle_eksternal_id,
          'driver_id' => $request->driver_eksternal_id
        ];
      }

      $delivery=DeliveryOrderDriver::create($unit_create);
      if ($request->is_internal) {
        Vehicle::find($request->vehicle_internal_id)->update([
          'delivery_id' => $delivery->id
        ]);
        DeliveryOrderStatusLog::storeAsStartedByDriver([
            'delivery_order_driver_id' => $delivery->id
        ]);
      } else {
        DeliveryOrderStatusLog::storeAsStartedByVendor([
            'delivery_order_driver_id' => $delivery->id
        ]);
        if ($request->vehicle_eksternal_id) {
          Vehicle::find($request->vehicle_eksternal_id)->update([
            'delivery_id' => $delivery->id
          ]);
        }
      }
      $m->update($unit_update);
      DB::commit();

      return Response::json(null);
    }

     public function edit_cost($id)
    {
      $item= ManifestCost::find($id);
      return Response::json($item,200,[],JSON_NUMERIC_CHECK);
    }

    public function edit_delivery($id)
    {
        $data['delivery'] = DeliveryOrderDriver::where('manifest_id', $id)->first();
        $data['item'] = Manifest::find($id);
        $data['customer'] = Contact::whereRaw("is_pelanggan = 1")
            ->select('id','name','address')->get();

        $data['vendor'] = DB::table('contacts')
            ->where('is_vendor', 1)
            ->selectRaw('id,name')->get();

        $data['driver_internal']=DB::table('contacts')->leftJoin('vehicle_contacts','vehicle_contacts.contact_id','contacts.id')->whereRaw("is_driver = 1 and is_internal = 1")->selectRaw('contacts.id,name,ifnull(concat("[",group_concat(vehicle_contacts.vehicle_id),"]"),"[]") as vehicle_list')->groupBy('contacts.id')->get();
        $data['driver_eksternal']=DB::table('contacts')->leftJoin('vehicle_contacts','vehicle_contacts.contact_id','contacts.id')->whereRaw("is_driver = 1 and is_internal = 0")->selectRaw('contacts.id,name,ifnull(concat("[",group_concat(vehicle_contacts.vehicle_id),"]"),"[]") as vehicle_list, parent_id')->groupBy('contacts.id')->get();
        $data['vehicle_internal']=DB::table('vehicles')->where('is_internal', 1)->selectRaw('id,nopol')->get();
        $data['vehicle_eksternal']=DB::table('vehicles')->where('is_internal', 0)->selectRaw('id,nopol,supplier_id as vendor_id')->get();
        $data['detail']=DB::table('manifest_details')
        ->leftJoin('job_order_details','job_order_details.id','manifest_details.job_order_detail_id')
        ->where('manifest_details.header_id', $id)
        ->selectRaw('
        group_concat(job_order_details.item_name) as item_name
        ')->groupBy('manifest_details.header_id')->first();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function update_delivery(Request $request, $id)
    {
      // dd($request);
      $validates=[
        'is_internal' => 'required',
        'vendor_id' => 'required_if:is_internal,0',
        'driver_internal_id' => 'required_if:is_internal,1',
        'delivery_order_number' => 'required_if:is_internal,0',
      ];

      $request->validate($validates,[
        'vendor_id.required_if' => 'Vendor harus dimasukkan jika memilih Eksternal',
        'driver_internal_id.required_if' => 'Driver harus dimasukkan jika memilih Internal',
        'delivery_order_number.required_if' => 'Driver harus dimasukkan jika memilih Eksternal',
        'vehicle_id.required_without' => 'Kendaraan harus dimasukkan jika driver dipilih'
      ]);

      DB::beginTransaction();

      $m=Manifest::find($id);

      $delivery = DeliveryOrderDriver::where('manifest_id', $id)->first();
      $delivery_update = [
        'from_id' => $request->from_id,
        'from_address_id' => $request->from_address_id,
        'to_id' => $request->to_id,
        'to_address_id' => $request->to_address_id,
        'commodity_name' => $request->commodity_name,
        'is_internal' => $request->is_internal,
        // 'pick_date' => createTimestamp($request->pick_date,$request->pick_time),
        // 'finish_date' => createTimestamp($request->finish_date,$request->finish_time)
      ];

      if($request->is_internal == 1) {
        $delivery_update['driver_id'] = $request->driver_internal_id;
        $delivery_update['vehicle_id'] = $request->vehicle_internal_id;
        $delivery_update['job_status_id'] = 2;

        JobStatusHistory::create([
          'delivery_id' => $delivery->id,
          'job_status_id' => 2,
          'vehicle_id' => $request->vehicle_internal_id,
          'driver_id' => $request->driver_internal_id
        ]);

        if ($request->vehicle_internal_id) {
          DB::table('vehicles')->where('delivery_id', $delivery->id)->update([
            'delivery_id' => null
          ]);
          Vehicle::find($request->vehicle_internal_id)->update([
            'delivery_id' => $delivery->id
          ]);
        }

        $unit_update = [
          'is_internal_driver' => 1,
          'vehicle_id' => $request->vehicle_internal_id,
          'driver_id' => $request->driver_internal_id
        ];
      } else {
        $delivery_update['vendor_id'] = $request->vendor_id;
        $delivery_update['driver_id'] = $request->driver_eksternal_id;
        $delivery_update['vehicle_id'] = $request->vehicle_eksternal_id;
        $delivery_update['code'] = $request->delivery_order_number;

        if ($request->driver_eksternal_id) {
          $delivery_update['job_status_id'] = 2;
          JobStatusHistory::create([
            'delivery_id' => $delivery->id,
            'job_status_id' => 2,
            'vehicle_id' => $request->vehicle_eksternal_id,
            'driver_id' => $request->driver_eksternal_id
          ]);
        } else {
          $delivery_update['job_status_id'] = 1;
          JobStatusHistory::create([
            'delivery_id' => $delivery->id,
            'job_status_id' => 1,
            'vendor_id' => $request->vendor_id,
          ]);
        }

        if ($request->driver_eksternal_id && !$request->vehicle_eksternal_id) {
          return Response::json(['message' => 'The given data was invalid.','errors' => ['vehicle_eksternal_id' => ['No Polis harus diisi jika driver dipilih!']]],422,[],JSON_NUMERIC_CHECK);
        }

        if ($request->vehicle_eksternal_id) {
          DB::table('vehicles')->where('delivery_id', $delivery->id)->update([
            'delivery_id' => null
          ]);
          Vehicle::find($request->vehicle_eksternal_id)->update([
            'delivery_id' => $delivery->id
          ]);
        }

        $unit_update = [
          'is_internal_driver' => 0,
          'vehicle_id' => $request->vehicle_eksternal_id,
          'driver_id' => $request->driver_eksternal_id
        ];
      }

      $delivery->update($delivery_update);

      $m->update($unit_update);
      DB::commit();

      return Response::json(null);
    }

    public function store_submission($id)
    {
      $mc=ManifestCost::find($id);
      // $h=Manifest::find($mc->header_id);
      DB::beginTransaction();
      SubmissionCost::create([
        'company_id' => $mc->company_id,
        'relation_cost_id' => $mc->id,
        'type_transaction_id' => 22, //mnaifest
        'vendor_id' => $mc->vendor_id,
        'create_by' => auth()->id(),
        'date_submission' => Carbon::now(),
        'description' => $mc->cost_type->code.' - '.$mc->cost_type->name,
        'type_submission' => 2,
      ]);
      $mc->update([
        'status' => 2
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail($id)
    {
        try {
            MD::destroy($id);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 421);
        }
        return Response::json(['Data successfully deleted']);
    }


    public function delete_cost($id)
    {
      DB::beginTransaction();
      ManifestCost::find($id)->delete();
      DB::commit();
    }

    public function change_depart_arrive(Request $request, $id)
    {
      DB::beginTransaction();
      if (isset($request->depart_date) && isset($request->depart_time)) {
        $depart=createTimestamp($request->depart_date,$request->depart_time);
      }
      if (isset($request->arrive_date) && isset($request->arrive_time)) {
        $arrive=createTimestamp($request->arrive_date,$request->arrive_time);
      }

      $m = Manifest::find($id);
      $m->update([
        'depart' => $depart??null,
        'arrive' => $arrive??null,
        'container_no' => $request->container_no
      ]);

      if($depart != null) {
          $details = DB::table('job_order_details')
          ->join('manifest_details', 'manifest_details.job_order_detail_id', 'job_order_details.id')
          ->where('manifest_details.header_id', $id)
          ->whereNotNull('job_order_details.warehouse_receipt_detail_id')
          ->selectRaw("job_order_details.*, manifest_details.transported AS manifest_transported")
          ->get();

          foreach($details as $detail) {
              $rack = DB::table('racks')
              ->whereId($detail->rack_id)
              ->first();
              DB::table('stock_transactions')->insert([
                  'warehouse_id' => $rack->warehouse_id,
                  'rack_id' => $detail->rack_id,
                  'item_id' => $detail->item_id,
                  'warehouse_receipt_detail_id' => $detail->warehouse_receipt_detail_id,
                  'type_transaction_id' => 22,
                  'code' => $m->code,
                  'date_transaction' => Carbon::now(),
                  'description' => 'Pengeluaran Barang pada manifest' ,
                  'qty_keluar' => $detail->manifest_transported,
            ]);
          }
      }
      DB::commit();
      return Response::json(null);
    }

    public function print_sj($id)
    {
        $data['item']=Manifest::find($id);
        $data['detail']=DB::table('manifest_details as md')
        ->leftJoin('job_order_details as jod','jod.id','md.job_order_detail_id')
        ->leftJoin('pieces as pc','pc.id','jod.piece_id')
        ->where('md.header_id', $id)
        ->selectRaw('
        jod.*,
        md.transported as md_transport,
        pc.name as piece
        ')->get();
        $data['detail_one']=ManifestDetail::where('header_id', $id)->first();
        if(!($data['detail_one']->job_order_detail ?? false)) {
            $json = '
            {
                "job_order_detail" : { 
                    "job_order" : { 
                        "uniqid" : "", 
                        "receiver" : { 
                            "name" : "" 
                        } 
                    } 
                }
            }';
            $json = json_decode($json);
            $data['detail_one'] = $json;
        }

        $data['sj']=DB::table('delivery_order_drivers')
        ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
        ->leftJoin('contacts','contacts.id','delivery_order_drivers.driver_id')
        ->where('delivery_order_drivers.manifest_id', $id)
        ->selectRaw('
        delivery_order_drivers.*,
        if(vehicles.id is not null, vehicles.nopol, delivery_order_drivers.nopol) as nopol,
        if(contacts.id is not null, contacts.name, delivery_order_drivers.driver_name) as driver
        ')->first();
        return PDF::loadView('pdf.sj_driver', $data)->stream('SJ Driver.pdf');
    }

    public function cancel_delivery(Request $request, $id)
    {
        $delivery = DeliveryOrderDriver::where('manifest_id', $id)
            ->where('status','<',3)->first();

        if(!$delivery)
            return Response::json(['status' => "ERROR", 'message' => 'Data delivery tidak ditemukan'], 200, [], JSON_NUMERIC_CHECK);

        DB::beginTransaction();

        // update manifest set vehicle_id = null
        Manifest::find($id)->update([
          'vehicle_id' => null
        ]);

        $delivery->update([
            'status' => 3,
            'cancelled_by' => auth()->id(),
            'cancel_reason' => $request->reason
        ]);

        DB::commit();

        return Response::json(['status' => "OK", 'message' => 'Delivery berhasil dicancel'], 200, [], JSON_NUMERIC_CHECK);
    }
}

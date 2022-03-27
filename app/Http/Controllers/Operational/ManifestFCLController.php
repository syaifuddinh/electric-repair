<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Manifest;
use App\Model\ManifestDetail;
use App\Model\ManifestCost;
use App\Model\CostType;
use App\Model\Contact;
use App\Model\Company;
use App\Model\VehicleContact;
use App\Model\VehicleType;
use App\Model\DeliveryOrderDriver;
use App\Model\SubmissionCost;
use App\Model\Vehicle;
use App\Model\Container;
use App\Model\Vessel;
use App\Model\VoyageSchedule;
use App\Model\JobOrderDetail;
use App\Model\Route as Trayek;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use App\Jobs\HitungJoCostManifestJob;
use App\Abstracts\Operational\ManifestDetail AS MD;

class ManifestFCLController extends Controller
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

    /*
      Date : 09-04-2020
      Description : Menyalin biaya kontainer ke packing list
      Developer : Didin
      Status : Create
    */
    public function setContainerCost($manifest_id, $old_container_id, $new_container_id)
    {
        if($old_container_id != $new_container_id) {
            $container = DB::table('containers')
            ->whereId($new_container_id)
            ->first();
            $manifest = DB::table('manifests')
            ->whereId($manifest_id)
            ->first();

            $route_costs = DB::table('route_costs')
            ->whereContainerTypeId($container->container_type_id)
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['route']=Trayek::select('id','name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function change_vessel($id)
    {
      $data['item']=Manifest::with('container')->where('id', $id)->first();
      $data['voyage']=VoyageSchedule::with('vessel')->get();
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
        'container_id' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'manifest');
      $code->setCode();
      $trx_code = $code->getCode();

      $con=Container::find($request->container_id);
      $i=Manifest::create([
        'company_id' => $request->company_id,
        'route_id' => $request->route_id,
        'transaction_type_id' => 22,
        'reff_no' => $request->reff_no,
        'code' => $trx_code,
        'create_by' => auth()->id(),
        'date_manifest' => Carbon::parse($request->date_manifest),
        'is_container' => 1,
        'is_full' => $request->is_full,
        'description' => $request->description,
        'container_id' => $request->container_id,
        'container_type_id' => $con->container_type_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function store_vessel(Request $request, $id)
    {
      $request->validate([
        'voyage_id' => 'required'
      ]);
      DB::beginTransaction();
      $voy=VoyageSchedule::find($request->voyage_id);
      $man=Manifest::find($id);
      Manifest::find($id)->update([
        'etd_time' => $voy->etd,
        'eta_time' => $voy->eta,
      ]);
      Container::where('id', $man->container_id)->update([
        'voyage_schedule_id' => $request->voyage_id,
        'vessel_id' => $request->vessel_id,
      ]);

      DB::commit();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function store_vehicle(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'driver' => 'required',
        'nopol' => 'required',
        'is_internal' => 'required',
        'code_sj' => 'required_if:is_internal,0',
      ],[
        'nopol.required' => 'Kendaraan Harus Diisi',
        'driver.required' => 'Driver Harus Diisi',
        'code_sj.required_if' => 'No. Surat Jalan Wajib diisi ketika menggunakan vendor!'
      ]);
      DB::beginTransaction();
      $input=$request->except(['is_internal','code_sj','is_edit','do_id']);
      $m=Manifest::find($id);
      if ($request->is_edit) {
        $m->update($input);
        DeliveryOrderDriver::find($request->do_id)->update([
          'is_internal' => $request->is_internal,
          'vehicle_id' => $request->vehicle_id,
          'driver_id' => $request->driver_id,
          'nopol' => $request->nopol,
          'driver_name' => $request->driver,
          'code' => $request->code_sj
        ]);

        DB::commit();
        return Response::json(null,200,[],JSON_NUMERIC_CHECK);
      }

      if ($request->is_internal==1) {
        $code = new TransactionCode($m->company_id, 'deliveryOrderDriver');
        $code->setCode();
        $trx_code = $code->getCode();
      } else {
        $trx_code = $request->code_sj;
      }
      $route=Trayek::find($m->route_id);
      $unit_create = [
        'manifest_id' => $id,
        'vehicle_id' => $request->vehicle_id,
        'driver_id' => $request->driver_id,
        // 'from_id' => $request->pick_id,
        // 'from_address_id' => $request->pick_address_id,
        // 'to_id' => $request->end_id,
        // 'to_address_id' => $request->end_address_id,
        // 'commodity_name' => $request->commodity_name,
        // 'pick_date' => createTimestamp($request->pick_date,$request->pick_time),
        // 'finish_date' => createTimestamp($request->finish_date,$request->finish_time),
        'code' => $trx_code,
        'create_by' => auth()->id(),
        'nopol' => $request->nopol,
        'driver_name' => $request->driver,
        'job_status_id' => 2
      ];
      DeliveryOrderDriver::create($unit_create);
      $m->update($input);
      DB::commit();

      return Response::json(null,200);
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
      $data['route']=Trayek::select('id','name')->get();
      $data['item']=Manifest::with('container')->where('id', $id)->first();
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
        'date_manifest' => 'required',
        'route_id' => 'required',
        'container_id' => 'required',
      ]);
      DB::beginTransaction();
      $con=Container::find($request->container_id);
      $m=Manifest::find($id);
      $old_container_id = $m->container_id;
      $m->update([
        'company_id' => $request->company_id,
        'route_id' => $request->route_id,
        'reff_no' => $request->reff_no,
        'date_manifest' => Carbon::parse($request->date_manifest),
        'is_container' => 1,
        'is_full' => $request->is_full,
        'description' => $request->description,
        'container_id' => $request->container_id,
        'container_type_id' => $con->container_type_id,
      ]);
      $this->setContainerCost($id, $old_container_id, $request->container_id);
      if($con->stuffing != null) {
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      Manifest::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function change_stuff_strip(Request $request, $container_id)
    {
      // dd($request);
      DB::beginTransaction();
      if (isset($request->stripping_date) && isset($request->stripping_time)) {
        $stripping=createTimestamp($request->stripping_date,$request->stripping_time);
      }
      if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
        $stuffing=createTimestamp($request->stuffing_date,$request->stuffing_time);
      }

      Container::find($container_id)->update([
        'stripping' => $stripping??null,
        'stuffing' => $stuffing??null,
      ]);

      if($stuffing != null) {
          $m = DB::table('manifests')
          ->whereId($request->manifest_id)
          ->first();
          $details = DB::table('job_order_details')
          ->join('manifest_details', 'manifest_details.job_order_detail_id', 'job_order_details.id')
          ->where('manifest_details.header_id', $request->manifest_id)
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

    public function store_revision(Request $request, $id)
    {
      // dd($request);
      // return Response::json(['id' => $id],500);
      $request->validate([
        'total_price' => 'required|integer|min:1'
      ]);
      DB::beginTransaction();
      ManifestCost::find($id)->update([
        'before_revision_cost' => $request->before_revision_cost,
        'total_price' => $request->total_price,
        'qty' => $request->qty,
        'price' => ($request->qty<=1?$request->total_price:$request->price),
        'vendor_id' => $request->vendor_id,
        'description' => $request->description,
        'status' => 6,
      ]);
      SubmissionCost::whereRaw("relation_cost_id = $id and type_submission = 2")->update([
        'status' => 5,
        'revision_date' => Carbon::now()
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function submit_price(Request $request, $id)
    {
      DB::beginTransaction();
      ManifestCost::find($id)->update([
        'vendor_id' => $request->vendor_id,
        'price' => $request->total_price,
        'total_price' => $request->total_price,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_price($id)
    {
      DB::beginTransaction();
      ManifestCost::find($id)->delete();
      DB::commit();
    }

    public function store_edit(Request $request, $id)
    {
        DB::beginTransaction();
        $leftover=0;
        if ($request->transported<$request->transported_origin) {
            $leftover=$request->transported_origin+$request->transported;
        } else {
            $leftover=$request->transported_origin-$request->transported;
        }

        $md=ManifestDetail::find($id);
        $manifestId = $md->header_id;

        if($md->job_order_detail_id) {
            JobOrderDetail::find($md->job_order_detail_id)->update([
                'transported' => DB::raw('transported+('.$request->transported.'-'.$md->transported.')'),
            ]);
        }

        $md->update([
            'requested_qty' => $request->requested_qty ?? 0,
            'discharged_qty' => $request->discharged_qty ?? 0,
            'transported' => $request->transported ?? 0,
            'leftover' => DB::raw('leftover+'.$leftover)
        ]);

        MD::updateTransportedQtyTotal($id);
        MD::doOutbound($id);

        if($md->job_order_detail_id) {
            $x = DB::table('job_order_details')
              ->select('job_order_details.qty')
              ->leftJoin('manifest_details', 'manifest_details.job_order_detail_id', '=', 'job_order_details.id')
              ->where('manifest_details.id', $id)
              ->first()
              ->qty;
            $y = DB::select("SELECT SUM(M2.transported) y FROM manifest_details AS M1 JOIN manifest_details AS M2 ON M2.job_order_detail_id = M1.job_order_detail_id WHERE M1.id = $id")[0]->y;

            if ($x - $y > 0){
                DB::commit();
                HitungJoCostManifestJob::dispatch($manifestId);
                return Response::json(null);
            }
        }

      DB::commit();
      return response()->json(['message' => ' Data successfully saved']);
    }
}

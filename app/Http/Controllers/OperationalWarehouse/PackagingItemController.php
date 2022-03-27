<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use Exception;

class PackagingItemController extends Controller
{


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list($packaging_id)
    {
        $dt = DB::table('packaging_items')
        ->wherePackagingId($packaging_id)
        ->get();

        return $dt;
    }

    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $packaging_id)
    {
      $request->validate([
        'item_name' => 'required',
      ], [
      ]);
      DB::beginTransaction();
      try {
          $params = [];
          $params['packaging_id'] = $packaging_id;
          $params['item_name'] = $request->item_name ?? '';
          $params['qty'] = $request->qty;
          DB::table('packaging_items')
          ->insert($params);
          DB::commit();
      } catch(Exception $e) {
        DB::rollback();

        return Response::json(['message' => $e->getMessage()], 421);
      }
      
      return Response::json(['message' => 'Data saved successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      
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

    }

    public function destroy_item_detail($id)
    {
      DB::beginTransaction();
      JobOrderDetail::find($id)->delete();
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
      // dd($id);
      DB::beginTransaction();
      $jo=JobOrder::find($id);

      WorkOrder::where('id', $jo->work_order_id)->update([
        'total_job_order' => DB::raw('total_job_order-1')
      ]);
      if ($jo->service_type_id==1) {
        foreach ($jo->detail as $value) {
          if ($value->imposition==1) {
            $qty=$value->volume;
          } elseif ($value->imposition==2) {
            $qty=$value->weight;
          } else {
            $qty=$value->qty;
          }
          WorkOrderDetail::find($jo->work_order_detail_id)->update([
            'qty_leftover' => DB::raw("qty_leftover+$qty")
          ]);
        }
      } else {
        WorkOrderDetail::find($jo->work_order_detail_id)->update([
          'qty_leftover' => DB::raw("qty_leftover+$jo->total_unit")
        ]);
      }
      $detail=JobOrderDetail::where('header_id', $id)->select('id')->get();
      foreach ($detail as $key => $value) {
        if (in_array($jo->service_type_id,[2,3,4])) {
          $md=ManifestDetail::where('job_order_detail_id', $value->id)->select('header_id')->first();
          if ($md) {
            Manifest::where('id', $md->header_id)->delete();
          }
        }
      }
      Packaging::where('job_order_id', $id)->delete();
      $jo->delete();
      DB::commit();
    }

    public function find_price(Request $request)
    {
      # code...
      $wod = WorkOrderDetail::find($request->work_order_detail_id);
    }
}

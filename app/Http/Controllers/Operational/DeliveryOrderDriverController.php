<?php

namespace App\Http\Controllers\Operational;

use App\Abstracts\Contact\ContactLocation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\DeliveryOrderDriver;
use App\Abstracts\Operational\DeliveryOrderStatusLog;
use App\Abstracts\Operational\ManifestDetail AS MD;
use App\Abstracts\Operational\DeliveryOrderDriverDocument;
use App\Abstracts\Operational\DeliveryOrderDriver AS DOD;
use Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryOrderDriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $item['status'] = DB::table('job_statuses')->selectRaw('id,name')->get();
        return Response::json($item,200,[],JSON_NUMERIC_CHECK);
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
        $data['item'] = DOD::show($id);

        $detail = MD::query(['delivery_order_id' => $id]);
        $detail = $detail->select(
            'job_order_details.item_name',        
            'job_order_details.weight',        
            'job_order_details.volume',        
            DB::raw('COALESCE(job_orders.code, sales_orders.code) AS job_order_code'),        
            'manifest_details.transported',        
            'pieces.name AS piece_name'        
        );
        $detail = $detail->get();
        $data['detail'] = $detail;

        $data['history'] = DeliveryOrderStatusLog::index($id); 
        $data['tracking'] = ContactLocation::showHistory($data['item']->driver_id);

        $data['documents'] = DeliveryOrderDriverDocument::index($id);
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
}

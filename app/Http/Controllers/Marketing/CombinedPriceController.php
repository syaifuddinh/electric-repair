<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CombinedPriceRequest;
use App\Model\CombinedPrice;
use App\Model\CombinedPriceDetail;
use App\Model\Company;
use App\Model\Service;
use Response;
use DB;

class CombinedPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['company'] = Company::select('id', 'name')->get();

        return Response::json($data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['company'] = Company::select('id', 'name')->get();
        $data['service']=Service::with('service_type')->select('id', 'name', 'service_type_id')->get();

        return Response::json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CombinedPrice $combinedPrice, CombinedPriceRequest $request)
    {
        //
        $request->validated();
        DB::beginTransaction();
        $combinedPrice->fill($request->toArray());
        $combinedPrice->total_item = count($request->detail);
        $combinedPrice->save();

        collect($request->detail)->each(function($value) use($combinedPrice) {
            $value['header_id'] = $combinedPrice->id;
            CombinedPriceDetail::create($value);
        });
        DB::commit();

        return Response::json(['message' => 'Transaksi berhasil di-input'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CombinedPrice  $combinedPrice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $combinedPrice = CombinedPrice::with('company:id,name')->findOrFail($id);
        $data['item'] = $combinedPrice;
        $data['detail'] = CombinedPriceDetail::with('service:id,name,service_type_id', 'service.service_type:id,name')->whereHeaderId($combinedPrice->id)->select('id', 'header_id', 'service_id')->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function service($id)
    {
        $service = Service::findOrFail($id);
        if($service->is_packet == 1) {

            $combinedPrice = CombinedPrice::with('company:id,name')->find($service->combined_price->id);
            $data['item'] = $combinedPrice;
            $data['detail'] = CombinedPriceDetail::with('service:id,name,service_type_id', 'service.service_type:id,name')->whereHeaderId($combinedPrice->id)->select('id', 'header_id', 'service_id')->get();
        } else {
            $data['item'] = null;
            $data['detail'] = [];
        }
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CombinedPrice  $combinedPrice
     * @return \Illuminate\Http\Response
     */
    public function edit(CombinedPrice $combinedPrice)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CombinedPrice  $combinedPrice
     * @return \Illuminate\Http\Response
     */
    public function update(CombinedPriceRequest $request, CombinedPrice $combinedPrice)
    {
        $request->validated();
        DB::beginTransaction();
        $combinedPrice->fill($request->toArray());
        $combinedPrice->total_item = count($request->detail);
        $combinedPrice->service()->update(['is_wh_rent' => 0]);
        $combinedPrice->save();
        $combinedPrice->detail()->delete();
        collect($request->detail)->each(function($value) use($combinedPrice) {
            $value['header_id'] = $combinedPrice->id;
            CombinedPriceDetail::create($value);
        });
        DB::commit();

        return Response::json(['message' => 'Transaksi berhasil di-input'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CombinedPrice  $combinedPrice
     * @return \Illuminate\Http\Response
     */
    public function destroy(CombinedPrice $combinedPrice)
    {
        //
        $combinedPrice->is_active = 0;
        $combinedPrice->save();

        return Response::json(['message' => 'Data berhasil dihapus'], 200);
    }

    public function activate(CombinedPrice $combinedPrice, $id)
    {
        //
        $combinedPrice = $combinedPrice->find($id);
        $combinedPrice->is_active = 1;
        $combinedPrice->save();

        return Response::json(['message' => 'Data berhasil diaktifkan'], 200);
    }
}

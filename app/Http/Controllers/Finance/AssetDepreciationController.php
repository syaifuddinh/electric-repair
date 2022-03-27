<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\AssetDepreciation;
use App\Model\Asset;
use Carbon\Carbon;
use DB;
use Response;

class AssetDepreciationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['asset']=DB::table('assets')->selectRaw('id,name')->get();
        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
      $ad=AssetDepreciation::find($id);
      $data['ad']=$ad;
      $data['item']=Asset::with('company','asset_group','account_accumulation','account_depreciation','account_asset')->where('id', $ad->header_id)->first();
      $tanggal = Carbon::parse()->toDateString();
      $data['item']['beban_akumulasi'] = DB::table('asset_depreciations')
          ->where('header_id','=',$id)
          ->where('date_utility','<',$tanggal)
          ->sum('depreciation_cost');
      $data['item']['nilai_buku'] = $data['item']['purchase_price'] - $data['item']['beban_akumulasi'];
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

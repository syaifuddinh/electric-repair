<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\AssetGroup;
use App\Model\Account;
use DB;
use Response;

class AssetGroupController extends Controller
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
      $data['account']=Account::with('parent')->where('is_base',0)->get();
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
        'code' => 'required|unique:asset_groups,code',
        'name' => 'required',
        'umur_ekonomis' => 'required',
        'account_asset_id' => 'required',
        'account_depreciation_id' => 'required',
        'account_accumulation_id' => 'required',
      ]);
      DB::beginTransaction();
      AssetGroup::create([
        'code' => $request->code,
        'name' => $request->name,
        'umur_ekonomis' => $request->umur_ekonomis,
        'method' => 1,
        'account_asset_id' => $request->account_asset_id,
        'account_depreciation_id' => $request->account_depreciation_id,
        'account_accumulation_id' => $request->account_accumulation_id,
        'description' => $request->description,
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['item']=AssetGroup::find($id);
      $data['account']=Account::with('parent')->where('is_base',0)->get();
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
        'code' => 'required|unique:asset_groups,code,'.$id,
        'name' => 'required',
        'umur_ekonomis' => 'required',
        'account_asset_id' => 'required',
        'account_depreciation_id' => 'required',
        'account_accumulation_id' => 'required',
      ]);
      DB::beginTransaction();
      AssetGroup::find($id)->update([
        'code' => $request->code,
        'name' => $request->name,
        'umur_ekonomis' => $request->umur_ekonomis,
        'method' => $request->method,
        'account_asset_id' => $request->account_asset_id,
        'account_depreciation_id' => $request->account_depreciation_id,
        'account_accumulation_id' => $request->account_accumulation_id,
        'description' => $request->description,
      ]);
      DB::commit();
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
      AssetGroup::find($id)->delete();
      DB::commit();
    }
}

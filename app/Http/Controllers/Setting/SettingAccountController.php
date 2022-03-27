<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AccountType;
use App\Model\Company;
use Response;
use DB;

class SettingAccountController extends Controller
{
    /*
      Date : 06-03-2020
      Description : Menampilkan semua akun
      Developer : Didin
      Status : Edit
    */
    public function index()
    {
        $data['account']=Account::with('parent')->whereRaw("is_base = 0")->select('id','code','name')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['header'] = Account::where('parent_id', null)->orderBy('code','asc')->get();
      $data['child'] = Account::where('parent_id','!=', null)->orderBy('code','asc')->get();
      $data['type'] = AccountType::all();
      $data['company'] = companyAdmin(auth()->id());

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
        'code' => 'required|unique:accounts',
        'name' => 'required',
        'jenis' => 'required',
        'group_report' => 'required',
        'type_id' => 'required',
      ]);
      DB::beginTransaction();
      $parent_id=null;
      $deep=0;
      if (isset($request->sub_sub_category)) {
        $parent_id=$request->sub_sub_category;
        $deep=3;
      } elseif (isset($request->sub_category)) {
        $parent_id=$request->sub_category;
        $deep=2;
      } elseif (isset($request->category)) {
        $parent_id=$request->category;
        $deep=1;
      }
      Account::create([
        'code' => $request->code,
        'name' => $request->name,
        'description' => $request->description,
        'deep' => $deep,
        'parent_id' => $parent_id,
        'is_base' => $request->is_base,
        'jenis' => $request->jenis,
        'group_report' => $request->group_report,
        'type_id' => $request->type_id,
        'no_cash_bank' => $request->no_cash_bank,
        'is_cash_count' => $request->is_cash_count ?? 0,
        'company_id' => $request->company_id
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
      $data['header'] = Account::where('parent_id', null)->orderBy('code','asc')->get();
      $data['child'] = Account::where('parent_id','!=', null)->orderBy('code','asc')->get();
      $data['type'] = AccountType::all();
      $data['item'] = Account::find($id);
      $data['company'] = companyAdmin(auth()->id());

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
        'code' => 'required|unique:accounts,code,'.$id,
        'name' => 'required',
        'jenis' => 'required',
        'group_report' => 'required',
        'type_id' => 'required',
      ]);

      DB::beginTransaction();
      $parent_id=null;
      $deep=0;
      if (isset($request->sub_sub_category)) {
        $parent_id=$request->sub_sub_category;
        $deep=3;
      } elseif (isset($request->sub_category)) {
        $parent_id=$request->sub_category;
        $deep=2;
      } elseif (isset($request->category)) {
        $parent_id=$request->category;
        $deep=1;
      }
      Account::find($id)->update([
        'code' => $request->code,
        'name' => $request->name,
        'description' => $request->description,
        'deep' => $deep,
        'parent_id' => $parent_id,
        'is_base' => $request->is_base,
        'jenis' => $request->jenis,
        'group_report' => $request->group_report,
        'type_id' => $request->type_id,
        'no_cash_bank' => $request->no_cash_bank,
        'is_cash_count' => $request->is_cash_count ?? 0,
        'company_id' => $request->company_id
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
      Account::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function get_account($id=null)
    {
      if (empty($id)) {
        $data = Account::all();
      } else {
        $data = Account::find($id);
      }

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}

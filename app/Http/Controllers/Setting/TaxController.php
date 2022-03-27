<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\Tax;
use Response;
use DB;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::table('taxes')
        ->select('id', 'code', 'name', 'non_npwp', 'npwp', 'pemotong_pemungut')
        ->get();

        return response()->json($data);
    }

    /*
      Date : 08-04-2020
      Description : Menampilkan pajak yang berstatus default 
                    invoice
      Developer : Didin
      Status : Create
    */
    public function default()
    {
        $tax = DB::table('taxes')
        ->whereIsDefault(1)
        ->select('id')
        ->get();

        return Response::json($tax, 200,[],JSON_NUMERIC_CHECK);
    }


    /*
      Date : 08-04-2020
      Description : Menampilkan pajak yang berstatus PPN
      Developer : Didin
      Status : Create
    */
    public function ppn()
    {
        $tax = DB::table('taxes')
        ->whereIsPpn(1)
        ->select('id', 'name', 'non_npwp', 'npwp', 'pemotong_pemungut')
        ->get();

        return Response::json($tax, 200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['account']=Account::where('is_base',0)->orderBy('code','asc')->get();

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
        'code' => 'required|unique:taxes',
        'name' => 'required',
        'akun_pembelian' => 'required',
        'akun_penjualan' => 'required',
        'akun_penjualan' => 'required',
        'pemotong_pemungut' => 'integer',
      ]);
      DB::beginTransaction();
      Tax::create([
        'akun_pembelian' => $request->akun_pembelian,
        'akun_penjualan' => $request->akun_penjualan,
        'code' => $request->code,
        'name' => $request->name,
        'non_npwp' => $request->non_npwp,
        'npwp' => $request->npwp,
        'is_default' => $request->is_default ?? 0,
        'is_ppn' => $request->is_ppn ?? 0,
        'pemotong_pemungut' => $request->pemotong_pemungut,
      ]);
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
      $data['account']=Account::where('is_base',0)->orderBy('code','asc')->get();
      $data['item']=Tax::find($id);

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
        'code' => 'required|unique:taxes,code,'.$id,
        'name' => 'required',
        'akun_pembelian' => 'required',
        'akun_penjualan' => 'required',
        'akun_penjualan' => 'required',
        'pemotong_pemungut' => 'integer',
      ]);

      DB::beginTransaction();
      Tax::find($id)->update([
        'akun_pembelian' => $request->akun_pembelian,
        'akun_penjualan' => $request->akun_penjualan,
        'code' => $request->code,
        'name' => $request->name,
        'non_npwp' => $request->non_npwp,
        'npwp' => $request->npwp,
        'is_ppn' => $request->is_ppn ?? 0,
        'is_default' => $request->is_default ?? 0,
        'pemotong_pemungut' => $request->pemotong_pemungut,
      ]);
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
      Tax::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }
}

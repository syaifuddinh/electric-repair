<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AccountDefault;
use Response;
use DB;

class AccountDefaultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code','asc')->get();
      $data['default']=AccountDefault::first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
      // dd($request);
      DB::beginTransaction();
      AccountDefault::where('id','!=',0)->update([
        'retur_barang' => $request->retur_barang,
        'biaya_klaim' => $request->biaya_klaim,
        'cek_giro_keluar' => $request->cek_giro_keluar,
        'cek_giro_masuk' => $request->cek_giro_masuk,
        'diskon_penjualan' => $request->diskon_penjualan,
        'hutang' => $request->hutang,
        'hutang_klaim' => $request->hutang_klaim,
        'laba_bulan_berjalan' => $request->laba_bulan_berjalan,
        'laba_ditahan' => $request->laba_ditahan,
        'laba_tahun_berjalan' => $request->laba_tahun_berjalan,
        'lebih_bayar_hutang' => $request->lebih_bayar_hutang,
        'lebih_bayar_piutang' => $request->lebih_bayar_piutang,
        'pendapatan_klaim' => $request->pendapatan_klaim,
        'penjualan' => $request->penjualan,
        'piutang' => $request->piutang,
        'ppn_in' => $request->ppn_in,
        'ppn_out' => $request->ppn_out,
        'saldo_awal' => $request->saldo_awal,
        'inventory' => $request->inventory,
        'perawatan' => $request->perawatan,
        'pendapatan_hibah' => $request->pendapatan_hibah,
        'bukti_potong'=>$request->bukti_potong,
        'bukti_potong_hutang'=>$request->bukti_potong_hutang,
        'account_kasbon_id'=>$request->account_kasbon_id,
        'pendapatan_reimburse'=>$request->pendapatan_reimburse,
        'pembelian'=>$request->pembelian,
        'account_cash'=>$request->account_cash
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

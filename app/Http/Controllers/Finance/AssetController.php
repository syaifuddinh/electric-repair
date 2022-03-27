<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\AssetGroup;
use App\Model\Account;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Asset;
use App\Model\AssetDepreciation;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\AccountDefault;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class AssetController extends Controller
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

    public function find($id, Request $request)
    {
        $data = DB::table('assets')
            ->where('id',$id)
            ->selectRaw('purchase_price, nilai_buku, beban_akumulasi')
            ->first();

        if(isset($request->tanggal))
            $tanggal = Carbon::parse($request->tanggal)->toDateString();
        else
            $tanggal = Carbon::parse()->toDateString();

        $data->beban_akumulasi = DB::table('asset_depreciations')
            ->where('header_id','=',$id)
            ->where('date_utility','<=',$tanggal)
            ->sum('depreciation_cost');
        $data->nilai_buku = $data->purchase_price - $data->beban_akumulasi;
        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['asset_group']=AssetGroup::all();
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
      // return response()->json($request,500);
      $request->validate([
        'code' => 'required|unique:assets,code',
        'company_id' => 'required',
        'asset_group_id' => 'required',
        'asset_type' => 'required',
        'name' => 'required',
        'umur_ekonomis' => 'required|min:1',
        'date_purchase' => 'required',
        'purchase_price' => 'required',
        'residu_price' => 'required',
        'account_asset_id' => 'required',
        'account_depreciation_id' => 'required',
        'account_accumulation_id' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, "saldoAsset");
      $code->setCode();
      $trx_code = $code->getCode();

      $bulanTerhitung=Carbon::parse($request->date_purchase)->format('F Y');
      $tanggalTerhitung=Carbon::parse('last day of '.$bulanTerhitung);

      $nilaiDisusutkan = $request->purchase_price - $request->residu_price;
      $bebanPerTahun = $nilaiDisusutkan / $request->umur_ekonomis;
      $bebanPerBulan = $bebanPerTahun / 12;

      $monthDiff = Carbon::now()->diffInMonths( Carbon::parse($request->date_purchase) );
      // dd($monthDiff);
      $j=Journal::create([
        'company_id' => $request->company_id,
        'type_transaction_id' => 34, //Asset
        'date_transaction' => Carbon::parse($request->date_purchase),
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'description' => 'Saldo Awal Asset Tetap '.$trx_code,
        'status' => 3
      ]);
      $nilaibuku = $request->purchase_price-($bebanPerBulan*$monthDiff);
      if ($nilaibuku<0)$nilaibuku=0;
      /*
      for ($i=0; $i < $monthDiff; $i++) {
        $nilai=$bebanPerBulan;
        if ($akumulasibeban+$bebanPerBulan>$nilaiDisusutkan)$nilai=$bebanPerBulan-($akumulasibeban+$bebanPerBulan-$nilaiDisusutkan);
        $akumulasibeban+=$nilai;
      }
      */
      $as=Asset::create([
        'company_id' => $request->company_id,
        'asset_group_id' => $request->asset_group_id,
        'asset_type' => $request->asset_type,
        'date_transaction' => Carbon::parse($request->date_transaction),
        'date_purchase' => Carbon::parse($request->date_purchase),
        'purchase_price' => $request->purchase_price,
        'residu_price' => $request->residu_price,
        'description' => $request->description,
        'code' => $request->code,
        'name' => $request->name,
        'umur_ekonomis' => $request->umur_ekonomis,
        'method' => $request->method,
        'account_asset_id' => $request->account_asset_id,
        'account_depreciation_id' => $request->account_depreciation_id,
        'account_accumulation_id' => $request->account_accumulation_id,
        'description' => $request->description,
        'is_saldo' => 1,
        'status' => 2,
        'journal_id' => $j->id,
        'create_by' => auth()->id(),
        'terhitung_tanggal' => $tanggalTerhitung,
        'beban_bulan' => $bebanPerBulan,
        'beban_tahun' => $bebanPerTahun,
        'beban_akumulasi' => 0,
        'nilai_buku' => $nilaibuku
      ]);

      $akumulasibeban=0;
      for ($i=0; $i < $monthDiff; $i++) {
        $bulanTerhitung=Carbon::parse($request->date_purchase)->format('F Y');
        $tanggalTerhitung=Carbon::parse('last day of '.$bulanTerhitung)->addMonths($i);
        $nilai=$bebanPerBulan;
        if ($akumulasibeban+$bebanPerBulan>$nilaiDisusutkan){
          $nilai=$bebanPerBulan-($akumulasibeban+$bebanPerBulan-$nilaiDisusutkan);
          if ($nilai<=0) {
            break;
          }
          AssetDepreciation::create([
            'header_id' => $as->id,
            'date_utility' => $tanggalTerhitung,
            'depreciation_cost' => $nilai
          ]);
          $akumulasibeban+=$nilai;
          break;
        }
        $akumulasibeban+=$nilai;
        if ($nilai<=0)break;
        AssetDepreciation::create([
          'header_id' => $as->id,
          'date_utility' => $tanggalTerhitung,
          'depreciation_cost' => $nilai
        ]);
      }
      DB::table('assets')->whereId($as->id)->update(['beban_akumulasi' => $akumulasibeban]);

      //jurnal asset
      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $request->account_asset_id,
        'debet' => $request->purchase_price,
        'credit' => 0,
        'description' => 'Saldo Awal Asset - '.$request->code.'-'.$request->name
      ]);
      //jurnal asset
      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $request->account_accumulation_id,
        'debet' => 0,
        'credit' => $akumulasibeban,
        'description' => 'Saldo Awal Asset - '.$request->code.'-'.$request->name
      ]);
      //jurnal saldo awal
      $account_default=AccountDefault::first();
      if (empty($account_default->saldo_awal)) {
        return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
      }
      JournalDetail::create([
        'header_id' => $j->id,
        'account_id' => $account_default->saldo_awal,
        'debet' => 0,
        'credit' => $request->purchase_price-$akumulasibeban,
        'description' => 'Saldo Awal Asset - '.$request->code.'-'.$request->name
      ]);
      DB::commit();
      return response()->json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=Asset::with('company','asset_group','account_accumulation','account_depreciation','account_asset')->where('id', $id)->first();
      $tanggal = Carbon::parse()->toDateString();
      $data['tgl'] = $tanggal;
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
      $data['data']=DB::table('assets')->whereId($id)->first();
      return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
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
      DB::beginTransaction();
      /*
      $asset = DB::table('assets')->whereId($id)->first();
      if ($asset->purchase_price!=$request->purchase_price) {
        $j = DB::table('journal_details')->where('header_id', $asset->journal_id)->selectRaw('id,debet,credit')->get();
        foreach ($j as $value) {
          $debet=0;$credit=0;
          if($value->debet>0)$debet=$request->purchase_price;
          if($value->credit>0)$credit=$request->purchase_price;
          DB::table('journal_details')->whereId($value->id)->update([
            'debet' => $debet,
            'credit' => $credit
          ]);
        }
      }
      */
      $asset = Asset::findOrFail($id);

      $asset->update([
        'company_id' => $request->company_id,
        'asset_group_id' => $request->asset_group_id,
        'asset_type' => $request->asset_type,
        'date_purchase' => Carbon::parse($request->date_purchase),
        // 'purchase_price' => $request->purchase_price,
        // 'residu_price' => $request->residu_price,
        'description' => $request->description,
        'code' => $request->code,
        'name' => $request->name,
        'umur_ekonomis' => $request->umur_ekonomis,
        'method' => $request->method,
        'account_asset_id' => $request->account_asset_id,
        'account_depreciation_id' => $request->account_depreciation_id,
        'account_accumulation_id' => $request->account_accumulation_id,
        'description' => $request->description,
      ]);
      DB::table('journals')
      ->whereId($asset->journal_id)
      ->update([
          'code' => $asset->code
      ]);
      DB::commit();

      return response()->json(['message' => 'Data berhasil ter-update']);
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
      $item = DB::table('assets')->where('id', $id)->first();
      DB::table('journals')->whereId($item->journal_id)->delete();
      DB::table('asset_depreciations')->whereHeaderId($id)->delete();
      DB::table('assets')->whereId($id)->delete();
      DB::commit();
      return response()->json(null);
    }

    /**
     * Penyusutan Aset
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function depreciate(Request $request, $id)
    {
        $asset = Asset::find($id);
        $tanggal = Carbon::parse($request->tanggal);
        $sum_depreciation = DB::table('asset_depreciations')
          ->where('header_id','=',$id)
          ->where('date_utility','<',$tanggal->toDateString())
          ->sum('depreciation_cost');
        $sum_depreciation += $request->nominal;

        DB::beginTransaction();

        AssetDepreciation::create([
            'header_id' => $asset->id,
            'date_utility' => $tanggal,
            'depreciation_cost' => $request->nominal
        ]);

        $code = new TransactionCode($asset->company_id, "saldoAsset");

        $code->setCode();
        $trx_code = $code->getCode();

        $jurnal = Journal::create([
            'company_id' => $asset->company_id,
            'type_transaction_id' => 41, //depresiasi asset
            'date_transaction' => $tanggal,
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'description' => 'Penyusutan Asset Tetap '.$trx_code
        ]);

        //jurnal asset
        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $asset->account_accumulation_id,
            'debet' => 0,
            'credit' => $request->nominal,
            'description' => 'Penyusutan Asset Tetap - '.$asset->code.'-'.$asset->name
        ]);

        //jurnal saldo awal
        $account_default = AccountDefault::first();
        if (empty($account_default->saldo_awal)) {
            return Response::json(['message' => 'Default "Akun Saldo Awal" belum ditentukan!'],500);
        }

        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $asset->account_depreciation_id,
            'debet' => $request->nominal,
            'credit' => 0,
            'description' => 'Penyusutan Asset Tetap - '.$asset->code.'-'.$asset->name
        ]);

        DB::commit();
    }
}

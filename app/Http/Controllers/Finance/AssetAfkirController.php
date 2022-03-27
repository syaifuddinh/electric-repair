<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Asset;
use App\Model\AssetAfkir;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\Journal;
use App\Model\JournalDetail;
use Carbon\Carbon;
use App\Utils\TransactionCode;
use DB;
use Response;

class AssetAfkirController extends Controller
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
      $data['company']=companyAdmin(auth()->id());
      $data['asset']=Asset::with('asset_group')->where('status', 2)->get();
      $data['account']=Account::with('parent')->where('is_base',0)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function find_asset($id)
    {
        $data=DB::table('assets')->where('id',$id)->selectRaw('beban_akumulasi')->first();
        return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
        'company_id' => 'required',
        'asset_id' => 'required',
        'account_loss_id' => 'required',
        'date_transaction' => 'required',
        'loss_amount' => 'required|min:1',
        'akumulasi_depresiasi' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'assetAfkir');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=AssetAfkir::create([
        'code' => $trx_code,
        'company_id' => $request->company_id,
        'asset_id' => $request->asset_id,
        'account_loss_id' => $request->account_loss_id,
        'create_by' => auth()->id(),
        'date_transaction' => Carbon::parse($request->date_transaction),
        'loss_amount' => $request->loss_amount,
        'description' => $request->description,
      ]);
      DB::commit();

      return Response::json(null,200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=AssetAfkir::with('company','asset','account_loss')->where('id', $id)->first();
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
        $data['company'] = companyAdmin(auth()->id());
        $data['asset'] = Asset::with('asset_group')->where('status', 2)->get();
        $data['account'] = Account::with('parent')->where('is_base',0)->get();
        $data['item'] = AssetAfkir::where('id', $id)->first();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function approve($id)
    {
        DB::beginTransaction();
        $item = AssetAfkir::find($id);
        $asset = Asset::find($item->asset_id);
        $asset->update([
            'status' => 3
        ]);
        $item->update([
            'status' => 2,
            'approve_date' => Carbon::now(),
            'approve_by' => \Auth::id()
        ]);

        $code = new TransactionCode($item->company_id, "afkirAsset");
        $code->setCode();
        $trx_code = $code->getCode();

        $jurnal = Journal::create([
            'company_id' => $item->company_id,
            'type_transaction_id' => 34, //invoice
            'date_transaction' => $item->date_transaction,
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'description' => 'Pengafkiran Asset Tetap '.$trx_code,
            'status' => 2
        ]);

        //jurnal afkir asset
        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $asset->account_asset_id,
            'debet' => 0,
            'credit' => $item->loss_amount,
            'description' => 'Pengafkiran Asset Tetap - '.$asset->code.'-'.$asset->name
        ]);

        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $item->account_loss_id,
            'debet' => $item->loss_amount,
            'credit' => 0,
            'description' => 'Pengafkiran Asset Tetap - '.$asset->code.'-'.$asset->name
        ]);

        DB::commit();

        return Response::json(null);
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
            'account_loss_id' => 'required',
            'date_transaction' => 'required',
            'loss_amount' => 'required',
            'akumulasi_depresiasi' => 'required',
        ]);

        DB::beginTransaction();

        AssetAfkir::find($id)->update([
            'company_id' => $request->company_id,
            'account_loss_id' => $request->account_loss_id,
            'date_transaction' => Carbon::parse($request->date_transaction),
            'loss_amount' => $request->loss_amount,
            'description' => $request->description,
        ]);

        DB::commit();

        return Response::json(null,200);
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

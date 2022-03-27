<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AssetSales;
use App\Model\AssetSalesDetail;
use App\Model\AssetGroup;
use App\Model\Asset;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\TypeTransaction;
use App\Utils\TransactionCode;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
use DB;
use Response;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class AssetSalesController extends Controller
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
        $data['asset_group'] = DB::table('asset_groups')->get();
        $data['cash_account'] = DB::table('accounts')->whereIn('no_cash_bank',[1,2])->get();
        $data['account'] = DB::table('accounts')->get();
        $data['asset'] = DB::table('assets')->where('status', 2)->get();
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
            'company_id' => 'required',
            'contact_id' => 'required',
            'date_transaction' => 'required',
            'termin' => 'required',
            'total_price' => 'required|min:1',
            'tempo' => 'required_if:termin,2',
            'cash_account_id' => 'required_if:termin,1',
        ]);

        DB::beginTransaction();
        
        $code = new TransactionCode($request->company_id, 'penjualanAsset');
        $code->setCode();
        $trx_code = $code->getCode();

        $sales = AssetSales::create([
            'company_id' => $request->company_id,
            'costumer_id' => $request->contact_id,
            'create_by' => auth()->id(),
            'date_transaction' => Carbon::parse($request->date_transaction),
            'code' => $trx_code,
            'termin' => $request->termin,
            'tempo' => ($request->termin==2?$request->tempo:null),
            'cash_account_id' => ($request->termin==1?$request->cash_account_id:null),
            'sales_account_id' => $request->sales_account_id,
            'total_price' => $request->total_price,
            'description' => $request->description
        ]);

        foreach ($request->detail as $key => $value) {
            if (empty($value)) {
            continue;
            }
            
            if(!isset($value['description']))
                $value['description'] = '';

            AssetSalesDetail::create([
            'header_id' => $sales->id,
            'asset_id' => $value["asset_id"],
            'price' => $value['price'],
            'nilai_buku' => $value['nilai_buku'],
            'description' => $value['description'],
            ]);
        }

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
      $data['item'] = AssetSales::with('company','costumer','cash_account', 'sales_account')
                        ->where('id', $id)
                        ->first();
      $data['details'] = AssetSalesDetail::with('asset')
                          ->where('header_id', $id)
                          ->get();
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
        $data['item'] = AssetSales::find($id);
        $data['details'] = AssetSalesDetail::where("header_id", $id)->get();
        $data['company'] = companyAdmin(auth()->id());
        $data['asset_group'] = DB::table('asset_groups')->get();
        $data['costumer'] = DB::table('contacts')->where('is_pelanggan', 1)->get();
        $data['cash_account'] = DB::table('accounts')->whereIn('no_cash_bank',[1,2])->get();
        $data['account'] = DB::table('accounts')->get();
        $data['asset'] = DB::table('assets')->where('status', 2)->get();
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
            'company_id' => 'required',
            'contact_id' => 'required',
            'date_transaction' => 'required',
            'termin' => 'required',
            'total_price' => 'required|min:1',
            'tempo' => 'required_if:termin,2',
            'cash_account_id' => 'required_if:termin,1',
        ]);

        DB::beginTransaction();

        $sales = AssetSales::find($id);

        $sales->update([
            'company_id' => $request->company_id,
            'costumer_id' => $request->contact_id,
            'date_transaction' => Carbon::parse($request->date_transaction),
            'termin' => $request->termin,
            'tempo' => ($request->termin==2?$request->tempo:null),
            'cash_account_id' => ($request->termin==1?$request->cash_account_id:null),
            'sales_account_id' => $request->sales_account_id,
            'total_price' => $request->total_price,
            'description' => $request->description
        ]);

        $details = AssetSalesDetail::where('header_id',$id)->get();
        $detail_ids = [];

        foreach ($request->detail as $value) {
            if (empty($value))
                continue;
            
            if(!isset($value['description']))
                $value['description'] = '';

            if(isset($value['id'])){
                $detail_ids []= $value['id'];
                AssetSalesDetail::find($value['id'])
                    ->update([
                        'asset_id' => $value["asset_id"],
                        'price' => $value['price'],
                        'nilai_buku' => $value['nilai_buku'],
                        'description' => $value['description'],
                    ]);
            } else {
                $detail = AssetSalesDetail::create([
                    'header_id' => $sales->id,
                    'asset_id' => $value["asset_id"],
                    'price' => $value['price'],
                    'nilai_buku' => $value['nilai_buku'],
                    'description' => $value['description'],
                ]);
                $detail_ids []= $detail->id;
            }
        }

        foreach($details as $detail){
            if(!in_array($detail->id, $detail_ids))
                $detail->delete();
        }

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
        
    }

    public function deleteDetail($id)
    {
        DB::beginTransaction();
        AssetSalesDetail::find($id)->delete();
        DB::commit();
    }

    /**
     * Approve pembelian aset
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, $id)
    {
        DB::beginTransaction();

        $sales = AssetSales::find($id);
        $trx_code = $sales->code;
        $isCash = isset($sales->cash_account_id) && !empty($sales->cash_account_id);
        $details = DB::table('asset_sales_details')->where('header_id', $id)->get();
        $jurnalDetails = [];
        $totalPemasukan = 0;
        $description = "Penjualan Asset {$trx_code}";
        $tp = TypeTransaction::where('slug','penjualanAsset')->first();

        $salesUpdate = [
            'approve_by' => auth()->id(),
            'status' => 2
        ];

        foreach ($details as $key => $value) {
            $asset = Asset::find($value->asset_id);
            $account = Account::find($asset->account_asset_id);
            $accumulationAccount = Account::find($asset->account_accumulation_id);
            $profitLoss = $value->price - $asset->nilai_buku;
            $totalPemasukan += $value->price;

            if(isset($jurnalDetails[':'.$account->id])) {
                $jurnalDetails[':'.$account->id]['value'] = 
                    $jurnalDetails[':'.$account->id]['value'] + $asset->purchase_price;
            } else {
                $jurnalDetails[':'.$account->id] = [
                    "jenis" => 2,
                    "account_id" => $account->id,
                    "value" => $asset->purchase_price
                ];
            }

            if($asset->beban_akumulasi > 0 && isset($jurnalDetails[':'.$accumulationAccount->id])) {
                $jurnalDetails[':'.$accumulationAccount->id]['value'] = 
                   $jurnalDetails[':'.$accumulationAccount->id]['value'] + $asset->beban_akumulasi;
            } else if ($asset->beban_akumulasi > 0) {
                $jurnalDetails[':'.$accumulationAccount->id] = [
                    "jenis" => 1,
                    "account_id" => $accumulationAccount->id,
                    "value" => $asset->beban_akumulasi
                ];
            }

            if($profitLoss == 0)
                continue;
            
            if(isset($jurnalDetails[':'.$sales->sales_account_id])) {
                $jurnalDetails[':'.$sales->sales_account_id]['value'] = 
                    $jurnalDetails[':'.$sales->sales_account_id]['value'] + $profitLoss;
                
                // Jurnal berpindah posisi jika minus
                if($jurnalDetails[':'.$sales->sales_account_id]['value'] < 0) {
                    $jurnalDetails[':'.$sales->sales_account_id]['value'] = 
                        abs($jurnalDetails[':'.$sales->sales_account_id]['value']);

                    if($jurnalDetails[':'.$sales->sales_account_id]['jenis'] == 1)
                        $jurnalDetails[':'.$sales->sales_account_id]['jenis'] = 2;
                    else
                        $jurnalDetails[':'.$sales->sales_account_id]['jenis'] = 1;
                }
            } else {
                $jurnalDetails[':'.$sales->sales_account_id] = [
                    'jenis' => ($profitLoss > 0) ? 2 : 1,
                    'account_id' => $sales->sales_account_id,
                    'value' => abs($profitLoss)
                ];
            }
        }

        if($isCash) {
            // Transaksi Kas
            $cashAccount = Account::find($sales->cash_account_id);

            $transaksiKas = CashTransaction::create([
                'company_id' => $sales->company_id,
                'type_transaction_id' => $tp->id,
                'code' => $sales->code,
                'reff' => $sales->code,
                'jenis' => 1, // Masuk
                'type' => $cashAccount->no_cash_bank,
                'description' => $description,
                'total' => $totalPemasukan,
                'account_id' => $cashAccount->id,
                'date_transaction' => $sales->date_transaction,
                'status_cost' => 3,
                'created_by' => auth()->id()
            ]);
            
            foreach($jurnalDetails as $jurnalDetail) {
                CashTransactionDetail::create([
                    'header_id' => $transaksiKas->id,
                    'account_id' => $jurnalDetail['account_id'],
                    'contact_id' => $sales->costumer_id,
                    'amount' => $jurnalDetail['value'],
                    'description' => $description,
                    'jenis' => $jurnalDetail['jenis']
                ]);
            }

            $jurnal = $transaksiKas->createJurnal();
        } else {
            // Hutang
            $dueDate = Carbon::parse($sales->date_transaction)
                ->add(CarbonInterval::days($sales->termin))
                ->format('Y-m-d');
            
            $receivable = Receivable::create([
                'company_id' => $sales->company_id,
                'contact_id' => $sales->costumer_id,
                'type_transaction_id' => $tp->id,
                'relation_id' => $sales->id,
                'created_by' => auth()->id(),
                'code' => $sales->code,
                'date_transaction' => $sales->date_transaction,
                'date_tempo' => $dueDate,
                'description' => $description,
                'debet' => $totalPemasukan
            ]);

            ReceivableDetail::create([
                'header_id' => $receivable->id,
                'type_transaction_id' => $tp->id,
                'relation_id' => $sales->id,
                'code' => $sales->code,
                'date_transaction' => $sales->date_transaction,
                'debet' => $totalPemasukan
            ]);

            $jurnal = $receivable->createJurnalPembentukan($jurnalDetails);
        }

        foreach ($details as $detail) {
            $asset = Asset::find($detail->asset_id);
            $assetUpdate = [ 
                'status' => 2,
                'journal_id' => $jurnal->id
            ];
            $asset->update($assetUpdate);
        }

        $salesUpdate['journal_id'] = $jurnal->id;
        $sales->update($salesUpdate);

        DB::commit();
    }
}
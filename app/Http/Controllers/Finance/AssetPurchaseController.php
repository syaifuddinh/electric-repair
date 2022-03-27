<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AssetPurchase;
use App\Model\AssetPurchaseDetail;
use App\Model\AssetGroup;
use App\Model\Asset;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\TypeTransaction;
use App\Utils\TransactionCode;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Payable;
use App\Model\PayableDetail;

use DB;
use Response;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use App\Abstracts\Finance\Asset\AssetPurchase AS AP;

class AssetPurchaseController extends Controller
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
      $data['asset_group']=DB::table('asset_groups')->get();
      $data['supplier']=DB::table('contacts')->where('is_supplier', 1)->get();
      $data['cash_account']=DB::table('accounts')->whereIn('no_cash_bank',[1,2])->get();
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
        'total_bayar' => 'required|min:1',
        'tempo' => 'required_if:termin,2',
        'cash_account_id' => 'required_if:termin,1',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'pembelianAsset');
      $code->setCode();
      $trx_code = $code->getCode();
    
      if(empty($trx_code)) {
        throw new \Exception('Kode Transaksi Kosong');
      }

      $i = AssetPurchase::create([
        'company_id' => $request->company_id,
        'supplier_id' => $request->contact_id,
        'create_by' => auth()->id(),
        'date_transaction' => Carbon::parse($request->date_transaction),
        'code' => $trx_code,
        'termin' => $request->termin,
        'tempo' => ($request->termin==2?$request->tempo:null),
        'cash_account_id' => ($request->termin==1?$request->cash_account_id:null),
        'total_price' => $request->total_bayar,
        'description' => $request->description
      ]);

      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $ag=AssetGroup::find($value['asset_group_id']);
        
        if(!isset($value['description']))
          $value['description'] = '';
        
          $as=Asset::create([
          'company_id' => $request->company_id,
          'asset_group_id' => $value['asset_group_id'],
          'code' => $value['code'],
          'name' => $value['name'],
          'asset_type' => $value['asset_type'],
          'date_transaction' => Carbon::parse($request->date_transaction),
          'date_purchase' => Carbon::parse($request->date_transaction),
          'purchase_price' => $value['price'],
          'residu_price' => $value['residu'],
          'description' => $value['description'],
          'umur_ekonomis' => $value['umur_ekonomis'],
          'method' => 1,
          'account_asset_id' => $ag->account_asset_id,
          'account_accumulation_id' => $ag->account_accumulation_id,
          'account_depreciation_id' => $ag->account_depreciation_id,
          'create_by' => auth()->id(),
          'status' => 1,
          'nilai_buku' => $value['price'],
        ]);

        AssetPurchaseDetail::create([
          'header_id' => $i->id,
          'asset_id' => $as->id,
          'price' => $value['price'],
          'residu' => $value['residu'],
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
      $data['item']=AssetPurchase::with('company','supplier','cash_account')->where('id', $id)->first();
      $data['details']=AssetPurchaseDetail::with('asset')
      ->leftJoin('assets', 'assets.id', 'asset_purchase_details.asset_id')
      ->where('asset_purchase_details.header_id', $id)
      ->select('asset_purchase_details.*', 'assets.name', 'assets.code', 'assets.asset_group_id', 'assets.asset_type', 'assets.umur_ekonomis')
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
        $request->validate([
            'company_id' => 'required',
            'contact_id' => 'required',
            'date_transaction' => 'required',
            'termin' => 'required',
            'total_bayar' => 'required|min:1',
            'tempo' => 'required_if:termin,2',
            'cash_account_id' => 'required_if:termin,1'
        ]);
        $status_code = 200;
        $msg = 'Data berhasil di-update';
        DB::beginTransaction();
        try {
            AP::validateIsApprove($id);

            $i = DB::table('asset_purchases')
            ->whereId($id)
            ->update([
                'company_id' => $request->company_id,
                'supplier_id' => $request->contact_id,
                'create_by' => auth()->id(),
                'date_transaction' => Carbon::parse($request->date_transaction),
                'termin' => $request->termin,
                'tempo' => ($request->termin==2?$request->tempo:null),
                'cash_account_id' => ($request->termin==1?$request->cash_account_id:null),
                'total_price' => $request->total_bayar,
                'description' => $request->description
            ]);

            DB::table('asset_purchase_details')
            ->whereHeaderId($id)
            ->delete();
            foreach ($request->detail as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                $ag=AssetGroup::find($value['asset_group_id']);

                if(!isset($value['description']))
                  $value['description'] = '';

                  $as = DB::table('assets')
                  ->whereCode($value['code'])
                  ->whereName($value['name'])
                  ->first();


                  if($as) {
                      DB::table('assets')
                      ->whereId($as->id)
                      ->update([
                          'asset_type' => $value['asset_type'],
                          'date_transaction' => Carbon::parse($request->date_transaction),
                          'date_purchase' => Carbon::parse($request->date_transaction),
                          'purchase_price' => $value['price'],
                          'residu_price' => $value['residu'],
                          'description' => $value['description'],
                          'umur_ekonomis' => $value['umur_ekonomis'],
                          'account_asset_id' => $ag->account_asset_id,
                          'account_accumulation_id' => $ag->account_accumulation_id,
                          'account_depreciation_id' => $ag->account_depreciation_id,
                          'nilai_buku' => $value['price']
                      ]);
                  } else {
                      $as=Asset::create([
                      'company_id' => $request->company_id,
                      'asset_group_id' => $value['asset_group_id'],
                      'code' => $value['code'],
                      'name' => $value['name'],
                      'asset_type' => $value['asset_type'],
                      'date_transaction' => Carbon::parse($request->date_transaction),
                      'date_purchase' => Carbon::parse($request->date_transaction),
                      'purchase_price' => $value['price'],
                      'residu_price' => $value['residu'],
                      'description' => $value['description'],
                      'umur_ekonomis' => $value['umur_ekonomis'],
                      'method' => 1,
                      'account_asset_id' => $ag->account_asset_id,
                      'account_accumulation_id' => $ag->account_accumulation_id,
                      'account_depreciation_id' => $ag->account_depreciation_id,
                      'create_by' => auth()->id(),
                      'status' => 1,
                      'nilai_buku' => $value['price'],
                    ]);
                  }


                AssetPurchaseDetail::create([
                  'header_id' => $id,
                  'asset_id' => $as->id,
                  'price' => $value['price'],
                  'residu' => $value['residu'],
                  'description' => $value['description'],
                ]);
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }

        $data['message'] = $msg;

        return response()->json($data, $status_code);
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
        $status_code = 200;
        $msg = 'Data berhasil disetujui';
        try {
            AP::validateIsApprove($id);
            $purchase = AssetPurchase::find($id);
            $trx_code = $purchase->code;
            $isCash = isset($purchase->cash_account_id) && !empty($purchase->cash_account_id);
            $details = DB::table('asset_purchase_details')->where('header_id', $id)->get();
            $journalDetail = [];
            $totalBiaya = 0;
            $description = "Pembelian Asset {$trx_code}";
            $tp = TypeTransaction::where('slug','pembelianAsset')->first();
            
            $purcasheUpdate = [
                'approve_by' => auth()->id(),
                'status' => 2
            ];

            foreach ($details as $key => $value) {
                $asset = Asset::find($value->asset_id);
                $account = Account::find($asset->account_asset_id);

                if(isset($jurnalDetails[':'.$account->id])) {
                    $jurnalDetails[':'.$account->id]['value'] = 
                        $jurnalDetails[':'.$account->id]['value'] + $value->price;
                } else {
                    $jurnalDetails[':'.$account->id] = [
                        "jenis" => 1,
                        "account_id" => $account->id,
                        "value" => $value->price
                    ];
                }

                $totalBiaya += $value->price;
            }

            
            if($isCash) {
                // Transaksi Kas
                $cashAccount = Account::find($purchase->cash_account_id);

                $transaksiKas = CashTransaction::create([
                    'company_id' => $purchase->company_id,
                    'type_transaction_id' => $tp->id,
                    'code' => $purchase->code,
                    'reff' => $purchase->code,
                    'jenis' => 2, // Keluar
                    'type' => $cashAccount->no_cash_bank,
                    'description' => $description,
                    'total' => $totalBiaya,
                    'account_id' => $cashAccount->id,
                    'date_transaction' => $purchase->date_transaction,
                    'status_cost' => 3,
                    'created_by' => auth()->id()
                ]);
                
                foreach($jurnalDetails as $jurnalDetail) {
                    CashTransactionDetail::create([
                        'header_id' => $transaksiKas->id,
                        'account_id' => $jurnalDetail['account_id'],
                        'contact_id' => $purchase->supplier_id,
                        'amount' => $jurnalDetail['value'],
                        'description' => $description,
                        'jenis' => $jurnalDetail['jenis']
                    ]);
                }

                $jurnal = $transaksiKas->createJurnal();
            } else {
                // Hutang
                $dueDate = Carbon::parse($purchase->date_transaction)
                    ->add(CarbonInterval::days($purchase->termin))
                    ->format('Y-m-d');
                
                $payable = Payable::create([
                    'company_id' => $purchase->company_id,
                    'contact_id' => $purchase->supplier_id,
                    'type_transaction_id' => $tp->id,
                    'created_by' => auth()->id(),
                    'code' => $purchase->code,
                    'date_transaction' => $purchase->date_transaction,
                    'description' => $description,
                    'date_tempo' => $dueDate,
                    'debet' => 0,
                    'credit' => $totalBiaya,
                ]);
        
                PayableDetail::create([
                    'header_id' => $payable->id,
                    'type_transaction_id' => $tp->id,
                    'code' => $purchase->code,
                    'date_transaction' => $purchase->date_transaction,
                    'description' => $description,
                    'debet' => 0,
                    'credit' => $totalBiaya,
                ]);

                $jurnal = $payable->createJurnalPembentukan($jurnalDetails);
            }

            $jurnal->update(['status' => 2]);
            foreach ($details as $detail) {
                $asset = Asset::find($detail->asset_id);
                $assetUpdate = [ 
                    'status' => 2,
                    'journal_id' => $jurnal->id
                ];
                $asset->update($assetUpdate);
            }

            $purcasheUpdate['journal_id'] = $jurnal->id;
            $purchase->update($purcasheUpdate);

            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
           
        $resp = [];
        $resp['message'] = $msg;

        return response()->json($resp, $status_code);
    }
}

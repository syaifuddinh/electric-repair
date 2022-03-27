<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Asset;
use App\Model\AssetDepreciation;
use App\Model\Closing;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\Company;
use App\Model\Contact;
use App\Model\TypeTransaction;
use App\Utils\TransactionCode;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Abstracts\Finance\Closing AS C;
use Response;
use DB;
use DataTables;
use Carbon\Carbon;

class ClosingController extends Controller
{
    /*
      Date        : 17-03-2020
      Description : Menampilkan data closing dalam format datatable
      Developer   : Didin 
      Status      : Edit
    */
    public function index()
    {
        $item = Closing::with('company:id,name');

        return DataTables::of($item)
        ->editColumn('closing_date', function($item){
        return date('d, M Y', strtotime($item->closing_date));
        })
        ->addColumn('periode', function($item){
        return date('d-M-Y', strtotime($item->start_periode)).' s/d '.date('d-M-Y', strtotime($item->end_periode));
        })
        ->editColumn('status_label', function($item){
        return !empty($item->status) ? "<span class=\"badge badge-danger\">Close</span>" : "<span class=\"badge badge-success\">Unclose</span>";
        })
        ->addColumn('action', function($item){
            $html = '';
            if($item->status == 0) {
                $html .= "<a title='Edit' ng-click='edit($item->id)' ng-show=\"roleList.includes('finance.closing.edit')\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";

            } else if($item->status == 1) {

                $html .= "<a title='Batalkan' ng-click='rollback($item->id)' ><span class='fa fa-close'></span></a>&nbsp;&nbsp;";
            }
            $month = Carbon::parse($item->start_periode)
            ->format('m');

            if($month == 12) {
                if($item->journal_id == null) {
                    if($item->status == 1) {
                        $html.="<a title='Posting jurnal akhir tahun' ng-click='posting($item->id)' ng-show=\"roleList.includes('finance.closing.posting')\"><span class='fa fa-check'></span></a>&nbsp;&nbsp;";
                    }

                } else {
                    if($item->status == 1) {
                        $html.="<a title='Batal posting jurnal akhir tahun' ng-click='cancelPosting($item->id)' ng-show=\"roleList.includes('finance.closing.cancel_posting')\"><span class='fa fa-close'></span></a>";
                    }
                }
            }

            return $html;
        })
        ->rawColumns(['status_label', 'periode', 'action'])
        ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['lastClosingDate']=Closing::where('status', 1)->orderBy('end_periode', 'DESC')->first(); $data['company']=companyAdmin(auth()->id());

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    /*
      Date        : 4 Maret 2020
      Description : count Laba Rugi Ditahan from closing
      Developer   : Dimas
      Status      : Edit
    */
    /*
      Date        : 24-03-2020
      Description : count Laba Rugi Ditahan from closing (Bug Fixing)
      Developer   : Didin
      Status      : Edit
    */
    public function store(Request $request)
    {
      // return $request->all();
      $request->validate([
        'companyId' => 'required',
        'periode' => 'required',
        'closingDate' => 'required',
        'isLock' => 'required',
      ]);

      $code = new TransactionCode($request->companyId, 'closing');
      $code->setCode();
      $trx_code = $code->getCode();

      $periode = new Carbon(date('Y-m-d', strtotime('01-'.$request->periode)));

      $startDate = dateDB($periode->startofMonth());
      $endDate = dateDB($periode->endofMonth());

      $closing = Closing::where('company_id', $request->companyId)->whereRaw("start_periode = $startDate or end_periode = $endDate")->get();
      if ( ! $closing->isEmpty() ) {
        return response(['errors' => ['periode closing sudah pernah dibuat.']], 422);
      }
      DB::beginTransaction();

      $closing = Closing::create([
          'company_id' => $request->companyId,
          'code' => $trx_code,
          'status' => 1, //close / unclose
          'start_periode' => $startDate,
          'end_periode' => $endDate,
          'closing_date' => dateDB($request->closingDate),
          'description' => $request->description,
          'is_lock' => $request->isLock,
          'is_depresiasi' => $request->isDepresiasi,
        // 'is_revaluasi' => $request->is_revaluasi,
      ]);

      /*
      NOT USED
      (By. Fajar) - 30/03/2020

      if( $month == 12 ){
          $journals = DB::table('journals')
          ->where('date_posting', '>=', $year . '-01-01')
          ->where('date_posting', '<=', $year . '-12-31')
          ->where('journals.id', '<>', $last_journal)
          ->join('journal_details', 'journals.id', '=', 'journal_details.header_id')
          ->join('accounts', 'journal_details.account_id', 'accounts.id')
          ->where('accounts.group_report', 2)
          ->select(
            'journal_details.account_id', 
            'journal_details.description', 
            DB::raw('SUM(journal_details.debet) AS debet'),
            DB::raw('SUM(journal_details.credit) AS credit')
          )
          ->groupBy('journal_details.account_id')
          ->select('journal_details.*', 'accounts.jenis')
          ->get();
        

        
          foreach($journals as $journal){
              if($journal->jenis == 1) {
                  DB::table('journal_details')->insert([
                      'header_id' => $last_journal,
                      'account_id' => $journal->account_id,
                      'debet' => 0,
                      'credit' => ($journal->debet - $journal->credit) < 0 ? ($journal->debet - $journal->credit) * -1 : ($journal->debet - $journal->credit),
                      'description' => $journal->description 
                  ]);
              } else {
                  DB::table('journal_details')->insert([
                      'header_id' => $last_journal,
                      'account_id' => $journal->account_id,
                      'debet' => ($journal->credit - $journal->debet) < 0 ? ($journal->credit - $journal->debet) * -1 : ($journal->credit - $journal->debet),
                      'credit' => 0,
                      'description' => $journal->description 
                  ]);                
              }
          }
          

          $journal_detail = DB::table('journal_details')
          ->where('header_id',  '=', $last_journal)
          ->select(DB::raw('SUM(debet) as total_debet'), DB::raw('SUM(credit) as total_credit'))
          ->first();

          // mencari apakah ada selisih untuk laba rugi ditahanZ 
          $account_default = DB::table('account_defaults')
          ->select('laba_ditahan')
          ->first();
          if($account_default->laba_ditahan == null) {
              return Response::json(['message' => 'Akun laba ditahan belum diisi di setting akun'], 421);
          }
          if($journal_detail->total_debet != $journal_detail->total_credit){
              $selisih = $journal_detail->total_debet - $journal_detail->total_credit;
            
              if($selisih > 0){
                  // positif (sisa di debet)
                  DB::table('journal_details')->insert([
                    'header_id' => $last_journal,
                    'account_id' => $account_default->laba_ditahan,
                    'debet' => 0,
                    'credit' => $selisih,
                    'description' => null 
                  ]);
              }
              else{
                  // negatif (sisa di credit)
                  DB::table('journal_details')->insert([
                    'header_id' => $last_journal,
                    'account_id' => $account_default->laba_ditahan,
                    'debet' => abs($selisih),
                    'credit' => 0,
                    'description' => null 
                  ]);
              }
          }
      }
      */

      //update kunci transaksi
      $typeTransaction = TypeTransaction::where('is_lock', true)->update([
        'last_date_lock' => dateDB($endDate)
      ]);

      if ($request->isDepresiasi) {
        // asset yang disetujui
        $assetInRangeClosing = Asset::where('status', 2)->whereBetween('terhitung_tanggal', array(dateDB($startDate), dateDB($endDate)))->get();

        // loop asset
        foreach ($assetInRangeClosing as $asset) {
          $depreciation = AssetDepreciation::create([
            'header_id' => $asset->id,
            'approve_by' => $asset->created_by,
            'date_approve' => date('Y-m-d'),
            'date_utility' => $asset->terhitung_tanggal,
            'depreciation_cost' => $asset->beban_bulan,
            'status' => 1,
          ]);

          $typetrx = TypeTransaction::where('slug', 'depresiasi')->first();

          $code = new TransactionCode($request->companyId, 'depresiasi');
          $code->setCode();
          $trx_code = $code->getCode();

          $journal=Journal::create([
            'company_id' => $request->companyId,
            'type_transaction_id' => $typetrx->id,
            'date_transaction' => dateDB($request->closingDate),
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'description' => $request->description,
            'debet' => 0,
            'credit' => 0,
            'status' => 2
          ]);

          $depreciation->journal_id = $journal->id;
          $depreciation->save();

          //biaya penyusutan
          JournalDetail::create([
            'header_id' => $journal->id,
            'account_id' => $asset->account_depreciation_id,
            'debet' => $asset->beban_bulan,
            'credit' => 0,
          ]);

          //biaya penyusutan
          JournalDetail::create([
            'header_id' => $journal->id,
            'account_id' => $asset->account_accumulation_id,
            'debet' => 0,
            'credit' => $asset->beban_bulan,
          ]);
        }
      }

      DB::commit();
      return Response::json(['message' => 'Data sucessfully saved'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=CashTransaction::with('company','type_transaction','account')->where('id', $id)->first();
      $data['detail']=CashTransactionDetail::with('account','contact')->where('header_id', $id)->get();
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
      $data['closing']=Closing::find($id); $data['company']=companyAdmin(auth()->id());

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /*
      Date        : 4 Maret 2020
      Description : include deleting journals and journal_details while unclosing
      Developer   : Dimas
      Status      : Edit
    */
    public function update(Request $request, $id)
    {
      $request->validate([
        'companyId' => 'required',
        'periode' => 'required',
        'closingDate' => 'required',
        'isLock' => 'required',
        // 'is_depresiasi' => 'required',
        // 'is_revaluasi' => 'required',
      ]);

      DB::beginTransaction();
      try {
        $periode = new Carbon(date('Y-m-d', strtotime('01-'.$request->periode)));
        $startDate = dateDB($periode->startofMonth());
        $endDate = dateDB($periode->endofMonth());

        $closing = Closing::where('company_id', $request->companyId)->whereRaw("start_periode = $startDate or end_periode = $endDate")->first();

        if ( !empty($closing) && ($closing->id == $id) ) {
          return response(['erros' => ['periode closing sudah pernah dibuat.']], 422);
        }

 
        $closing = Closing::find($id);
        $journal_id = $closing->journal_id;
        $closing->update([
          'company_id' => $request->companyId,
          'status' => $request->status, //close / unclose
          'start_periode' => $startDate,
          'end_periode' => $endDate,
          'closing_date' => dateDB($request->closingDate),
          'description' => $request->description,
          'is_lock' => $request->isLock,
          'is_depresiasi' => $request->isDepresiasi,
          'journal_id' => $request->status == 0 ? null : $journal_id 
          // 'is_revaluasi' => $request->is_revaluasi,
        ]);

        //update kunci transaksi
        $typeTransaction = TypeTransaction::where('is_lock', true)->update([
          'last_date_lock' => dateDB($endDate)
        ]);

        DB::commit();
      } catch (\Exception $e) {
        DB::rollback();
        return Response::json(['messages' => $e->getMessage()], 500);
      }

      return Response::json([], 200);
    }

    /*
      Date        : 18-04-2020
      Description : Membatalkan posting jurnal closing
      Developer   : Didin
      Status      : Create
    */
    public function cancelPosting($id) {
        $closing = DB::table('closing')
        ->whereId($id)
        ->first();

        if($closing == null) {
            return Response::json(['message' => 'Closing tidak ditemukan'], 422);
        }
        $journal_id = $closing->journal_id;
        if($journal_id != null) {
            DB::table('journal_details')
            ->where('header_id', '=', $journal_id)
            ->delete();

            DB::table('journals')
            ->where('id', '=', $journal_id)
            ->delete();
        }

        return Response::json(['messages' => 'Closing berhasil dibatalkan']);
    }

    /*
      Date        : 18-04-2020
      Description : Posting jurnal closing
      Developer   : Didin
      Status      : Create
    */
    public function posting($id) {
        $closing = DB::table('closing')
        ->whereId($id)
        ->first();

        if($closing == null) {
            return Response::json(['message' => 'Closing tidak ditemukan'], 422);
        }

        $code = new TransactionCode($closing->company_id, 53);
        $code->setCode();
        $trx_code = $code->getCode();
        $journal_id = DB::table('journals')
        ->insertGetId([
            'journals.company_id' => $closing->company_id,
            'journals.type_transaction_id' => 53,
            'journals.code' => $trx_code,
            'journals.date_transaction' => Carbon::now()->format('Y-m-d'),
            'journals.date_posting' => Carbon::now()->format('Y-m-d'),
            'journals.created_by' => auth()->id(),
            'journals.status' => 3,
            'journals.description' => $closing->description .' | Closing Jurnal',
            'journals.source' => 1
        ]);

        DB::table('closing')
        ->whereId($id)
        ->update([
            'journal_id' => $journal_id
        ]);
      $startDate = $closing->start_periode;
      $month = Carbon::parse($startDate)->format('m');
      $year = Carbon::parse($startDate)->format('Y');

      $accounts = DB::table('accounts')->where('group_report', 2)->selectRaw('id')->get();
      $totalDebet=0;
      $totalCredit=0;
      foreach ($accounts as $key => $value) {
        $debet=0;
        $credit=0;

        /* FETCH DATA */
        DB::table('journal_details')
        ->leftJoin('journals','journal_details.header_id','journals.id')
        ->whereRaw('journals.status = 3 and journal_details.account_id = ? and journals.company_id = ? and journals.date_transaction <= ?', [
          $value->id,
          $closing->company_id,
          $closing->end_periode
        ])
        ->selectRaw('
          sum(journal_details.debet) as debet,
          sum(journal_details.credit) as credit
        ')
        ->orderBy('journal_details.id')
        ->chunk(50, function($chunk) use (&$debet,&$credit) {
          foreach ($chunk as $key => $value) {
            $debet+=$value->debet;
            $credit+=$value->credit;
          }
        });

        if ($debet>$credit) {
          $debet_j=0;
          $credit_j=$debet-$credit;
        } else {
          $debet_j=$credit-$debet;
          $credit_j=0;
        }

        if ($debet_j==0&&$credit_j==0) {
          continue;
        }

        DB::table('journal_details')->insert([
          'header_id' => $journal_id,
          'account_id' => $value->id,
          'debet' => $debet_j,
          'credit' => $credit_j,
          'description' => 'Closing Jurnal'
        ]);

        $totalDebet+=$debet_j;
        $totalCredit+=$credit_j;
      }

      $account_default = DB::table('account_defaults')
      ->select('laba_ditahan')
      ->first();
      if(!$account_default->laba_ditahan) {
          return Response::json(['message' => 'Akun laba ditahan belum diisi di setting akun'], 421);
      }

      if ($totalDebet>$totalCredit) {
        $debet_j=0;
        $credit_j=$totalDebet-$totalCredit;
      } else {
        $debet_j=$totalCredit-$totalDebet;
        $credit_j=0;
      }
      DB::table('journal_details')->insert([
        'header_id' => $journal_id,
        'account_id' => $account_default->laba_ditahan,
        'debet' => $debet_j,
        'credit' => $credit_j,
        'description' => 'Closing Jurnal - Tutup Laba/Rugi'
      ]);

      return Response::json(['message' => 'Closing berhasil diposting']);
    }  

    /*
      Date : 27-05-2021
      Description : men-setujui penggunaan barang dan mengurangi stok 
                    pada inventori
      Developer : Didin
      Status : Create
    */
    public function rollback($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            C::rollback($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
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
}

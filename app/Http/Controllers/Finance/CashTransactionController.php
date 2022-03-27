<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Company;
use App\Model\TypeTransaction;
use App\Model\Account;
use App\Model\AccountDefault;
use App\Model\CashCategory;
use App\Model\JournalFavorite;
use App\Model\Contact;
use App\Model\CashAdvance;
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
use App\Model\SubmissionCost;
use App\Utils\TransactionCode;
use App\Abstracts\Finance\Closing;
use App\Abstracts\Finance\CashTransaction AS CT;
use Response;
use DB;
use Carbon\Carbon;
use Exception;

class CashTransactionController extends Controller
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
      $data['accountDefaultPiutang']=AccountDefault::first()->getPiutang;
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code')->get();
      // $data['cash_bank']=Account::with('parent')->where('is_base',0)->whereIn('no_cash_bank',[1,2])->orderBy('code')->get();
      $data['type_transaction']=TypeTransaction::all();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 21-03-2020
      Description : Membatalkan persetujuan transaksi kas
      Developer : Didin
      Status : Create
    */
    public function reject($id) {
        if(DB::table('cash_transactions')->whereId($id)->count('id') < 1) {
            return Response::json(['message' => 'Transaksi kas tidak ditemukan'], 404);
        }
        DB::beginTransaction();
        try {
            CT::validateWasRequested($id);
        } catch (Exception $e) {
            throw new Exception('Data sudah dibatalkan');
        }
        $ct =DB::table('cash_transactions')
        ->whereId($id)
        ->first();
        Closing::preventByDate($ct->date_transaction);
        $journal_id = $ct->journal_id;

        $ct =DB::table('cash_transactions')
        ->whereId($id);

        $ct->update([
            'status_cost' => 1,
            'status' => 1,
            'is_cut' => 0,
            'journal_id' => null
        ]);
        DB::commit();

        DB::table('journal_details')
        ->whereHeaderId($journal_id)
        ->delete();
        DB::table('journals')
        ->whereId($journal_id)
        ->delete();


        return Response::json(['message' => 'Transaksi kas berhasil dibatalkan']);
    }

    /*
      Date : 17-03-2020
      Description : Meng-update biaya manifest / biaya job order pada detail transaksi kas
      Developer : Didin
      Status : Edit
    */
    public function update_manifest(Request $request, $cash_transaction_detail_id)
    {
        DB::table('cash_transaction_details')
        ->whereId($cash_transaction_detail_id)
        ->update([
            'job_order_cost_id' => $request->job_order_cost_id ?? null,
            'manifest_cost_id' => $request->manifest_cost_id ?? null,
            'description' => $request->description ?? null,
        ]);

        Response::json(['message' => 'Data berhasil di-update']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /*
      Date : 04-03-2020
      Description : Menambah transaksi kas
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
        // dd($request->detail);
        $request->validate([
            'company_id' => 'required',
            'date_transaction' => 'required',
            'type' => 'required',
            'jenis' => 'required',
            // 'reff' => 'required',
            // 'cash_bank' => 'required',
        ]);

        if ($request->type==1 && $request->jenis==1) {
            $tyname="CashIn";
        } elseif ($request->type==1 && $request->jenis==2) {
            $tyname="CashOut";
        } elseif ($request->type==2 && $request->jenis==1) {
            $tyname="BankIn";
        } else {
            $tyname="BankOut";
        }

        DB::beginTransaction();
        $tp = TypeTransaction::where('slug', $tyname)->first();
        $code = new TransactionCode($request->company_id, $tyname);
        $code->setCode();
        $trx_code = $code->getCode();
        $statusCost = 1;

        if($request->kasbon_id > 0)
            $statusCost = 1;

        $i=CashTransaction::create([
            'company_id' => $request->company_id,
            'type_transaction_id' => $tp->id,
            'code' => $trx_code,
            'reff' => $request->reff,
            'jenis' => $request->jenis,
            'type' => $request->type,
            'description' => $request->description,
            'total' => 0,
            'account_id' => $request->cash_bank,
            'date_transaction' => dateDB($request->date_transaction),
            'status_cost' => $statusCost,
            'created_by' => auth()->id()
        ]);

        if ($request->detail) {
            // if ($request->jenis==1) {
            //     $j=Journal::create([
            //         'company_id' => $request->company_id,
            //         'type_transaction_id' => $tp->id,
            //         'date_transaction' => dateDB($request->date_transaction),
            //         'created_by' => auth()->id(),
            //         'code' => $trx_code,
            //         'description' => $request->description,
            //         'debet' => 0,
            //         'credit' => 0,
            //     ]);
            // }
            // CashTransaction::find($i->id)->update(['journal_id' => ($j->id??null)]);
            CashTransactionDetail::where('header_id', $i->id)->delete();

            $amount=0;
            foreach ($request->detail as $value) {
                if (empty($value))
                    continue;

                CashTransactionDetail::create([
                    'header_id' => $i->id,
                    'account_id' => $value['account_id'],
                    'amount' => $value['amount'],
                    'job_order_cost_id' => $value['job_order_cost_id'] ?? null,
                    'manifest_cost_id' => $value['manifest_cost_id'] ?? null,
                    'uploaded_file' => $value['file'],
                    'description' => @$value['description'],
                    'jenis' => $value['jenis']
                ]);

                if(!is_null($value['account_id'])) {
                    $cekCC=cekCashCount($request->company_id,$value['account_id']);

                    if ($cekCC)
                        return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
                }

                // if ($request->jenis==1) {
                //     JournalDetail::create([
                //     'header_id' => $j->id,
                //     'account_id' => $value['account_id'],
                //     // 'cash_category_id' => $cid,
                //     'debet' => ($request->jenis==2?$value['amount']:0),
                //     'credit' => ($request->jenis==1?$value['amount']:0),
                //     ]);
                // }

                $amount+=$value['amount'];
            }

            if(!is_null($request->cash_bank)) {
                $cekCC=cekCashCount($request->company_id,$request->cash_bank);

                if ($cekCC)
                    return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
            }

            // if ($request->jenis==1) {
            // JournalDetail::create([
            //     'header_id' => $j->id,
            //     'account_id' => $request->cash_bank,
            //     // 'cash_category_id' => $cid,
            //     'debet' => ($request->jenis==1?$amount:0),
            //     'credit' => ($request->jenis==2?$amount:0),
            // ]);
            // }

            $i->update([ 'total' => $amount ]);
        }

        if($request->kasbon_id > 0) {
            $ca = CashAdvance::find($request->kasbon_id);
            $ca->update(['cash_transaction_id' => $i->id]);
        }

        DB::commit();

        return Response::json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /*
      Date : 04-03-2020
      Description : Menampilkan detail transaksi kas
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
      $item = CashTransaction::find($id);

      $data['item'] = CashTransaction::with('company','type_transaction','account')
      ->leftJoin('cash_transaction_cost_statuses', 'cash_transaction_cost_statuses.id', 'cash_transactions.status_cost')
      ->where('cash_transactions.id', $id)
      ->select(
        'cash_transactions.*',
        'cash_transaction_cost_statuses.slug AS status_cost_slug',
        'cash_transaction_cost_statuses.name AS status_cost_name'
      )
      ->first();

      $data['can_approve'] = $item->couldBeApproved();
      $data['detail']=CashTransactionDetail::with('account','contact')
      ->leftJoin('manifest_costs', 'manifest_costs.id', 'cash_transaction_details.manifest_cost_id')
      ->leftJoin('manifests', 'manifest_costs.header_id', 'manifests.id')
      ->leftJoin('cost_types AS C1', 'C1.id', 'manifest_costs.cost_type_id')
      ->leftJoin('job_order_costs', 'job_order_costs.id', 'cash_transaction_details.job_order_cost_id')
      ->leftJoin('job_orders', 'job_order_costs.header_id', 'job_orders.id')
      ->leftJoin('cost_types AS C2', 'C2.id', 'job_order_costs.cost_type_id')
      ->where('cash_transaction_details.header_id', $id)
      ->selectRaw('cash_transaction_details.*, IFNULL(C1.name, C2.name) AS name, IFNULL(manifests.code, job_orders.code) AS code')
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
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code')->get();
      $data['type_transaction']=TypeTransaction::all();
      $data['company']=companyAdmin(auth()->id());
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();
      $data['vendor']=Contact::where('is_vendor', 1)->where('vendor_status_approve', 2)->select('id','name')->get();
      $data['item']=CashTransaction::find($id);
      $data['detail']=CashTransactionDetail::with('account','contact')
      ->leftJoin('manifest_costs', 'manifest_costs.id', 'cash_transaction_details.manifest_cost_id')
      ->leftJoin('manifests', 'manifest_costs.header_id', 'manifests.id')
      ->leftJoin('cost_types AS C1', 'C1.id', 'manifest_costs.cost_type_id')
      ->leftJoin('job_order_costs', 'job_order_costs.id', 'cash_transaction_details.job_order_cost_id')
      ->leftJoin('job_orders', 'job_order_costs.header_id', 'job_orders.id')
      ->leftJoin('cost_types AS C2', 'C2.id', 'job_order_costs.cost_type_id')
      ->where('cash_transaction_details.header_id', $id)
      ->selectRaw('cash_transaction_details.*, IFNULL(C1.name, C2.name) AS name, IFNULL(manifests.code, job_orders.code) AS code')
      ->get();
      // $data['detail']=CashTransactionDetail::where('header_id', $id)->get();
      if ($data['item']->is_cut==1) {
        return Response::json(['message' => 'Data sudah tidak dapat di edit'],500);
      }
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
      Date : 04-03-2020
      Description : Mengedit transaksi kas
      Developer : Didin
      Status : Edit
    */
    public function update(Request $request, $id)
    {
        // dd($request);
        $request->validate([
            'company_id' => 'required',
            'date_transaction' => 'required',
            'type' => 'required',
            'jenis' => 'required',
            // 'reff' => 'required',
            // 'cash_bank' => 'required',
            'detail' => 'required',
        ]);

        if ($request->type==1 && $request->jenis==1) {
            $tyname="CashIn";
        } elseif ($request->type==1 && $request->jenis==2) {
            $tyname="CashOut";
        } elseif ($request->type==2 && $request->jenis==1) {
            $tyname="BankIn";
        } else {
            $tyname="BankOut";
        }
        DB::beginTransaction();
        // $asli=CashTransaction::find($id);
        // dd($asli);
        // if (isset($asli->journal_id)) {
        //     Journal::find($asli->journal_id)->delete();
        // }

        $tp = TypeTransaction::where('slug', $tyname)->first();
        $code = new TransactionCode($request->company_id, $tyname);
        $code->setCode();
        $trx_code = $code->getCode();
        $i = CashTransaction::find($id);
        $i->update([
            'description' => $request->description,
            'account_id' => $request->cash_bank,
        ]);

        //   if ($request->jenis==1) {
        //     $j=Journal::create([
        //       'company_id' => $request->company_id,
        //       'type_transaction_id' => $tp->id,
        //       'date_transaction' => dateDB($request->date_transaction),
        //       'created_by' => auth()->id(),
        //       'code' => $trx_code,
        //       'description' => $request->description,
        //       'debet' => 0,
        //       'credit' => 0,
        //     ]);
        //   }
        // CashTransaction::find($i->id)->update(['journal_id' => $j->id??null]);
        // CashTransactionDetail::where('header_id', $i->id)->delete();

        $amount=0;
        foreach ($request->detail as $value) {
            if (empty($value))
                continue;

            $data = [
                'header_id' => $i->id,
                'account_id' => $value['account_id'],
                'uploaded_file' => $value['file'],
                'amount' => $value['amount'],
                'description' => @$value['description'],
                'jenis' => $value['jenis'],
                'job_order_cost_id' => $value['job_order_cost_id'] ?? null,
                'manifest_cost_id' => $value['manifest_cost_id'] ?? null
            ];

            if($value['id'] == 0)
                CashTransactionDetail::create($data);
            else
                CashTransactionDetail::whereId($value['id'])->update($data);

            $cekCC = cekCashCount($request->company_id,$value['account_id']);
            if ($cekCC) {
                return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
            }

            // if ($request->jenis==1) {
            // JournalDetail::create([
            //     'header_id' => $j->id,
            //     'account_id' => $value['account_id'],
            //     // 'cash_category_id' => $cid,
            //     'debet' => ($request->jenis==2?$value['amount']:0),
            //     'credit' => ($request->jenis==1?$value['amount']:0),
            // ]);
            // }

            $amount+=$value['amount'];
        }

        $cekCC=cekCashCount($request->company_id,$request->cash_bank);
        if ($cekCC) {
            return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        // if ($request->jenis==1) {
        //     JournalDetail::create([
        //         'header_id' => $j->id,
        //         'account_id' => $request->cash_bank,
        //         // 'cash_category_id' => $cid,
        //         'debet' => ($request->jenis==1?$amount:0),
        //         'credit' => ($request->jenis==2?$amount:0),
        //     ]);
        // }

        $i->update([
            'total' => $amount,
        ]);

        //   $asli->update([
        //     'edit_count' => DB::raw('edit_count+1'),
        //     'status' => 2,
        //     'is_cut' => 1
        //   ]);
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
      $i=CashTransaction::find($id);
      Closing::preventByDate($i->date_transaction);
      if (isset($i->journal_id)) {
        Journal::find($i->journal_id)->delete();
      }
      $i->update([
        'status' => 3,
        'is_cut' => 1
      ]);
      DB::commit();
    }

    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            CT::validateWasFinished($id);
            $ct=CashTransaction::find($id);
            Closing::preventByDate($ct->date_transaction);
            $error_akun = false;

            if(!$ct->account_id)
                return Response::json(['message' => 'Akun pada data belum lengkap'], 500);

            $details=DB::table('cash_transaction_details')->where('header_id', $id)->selectRaw('id,account_id,amount,description,job_order_cost_id,manifest_cost_id')->get();
            foreach($details as $detail) {
                if($detail->job_order_cost_id) DB::update("UPDATE job_order_costs SET is_invoice = 1 WHERE id = {$detail->job_order_cost_id}");
                if($detail->manifest_cost_id) DB::update("UPDATE manifest_costs SET is_invoice = 1 WHERE id = {$detail->manifest_cost_id}");
                if(!$detail->account_id)
                    return Response::json(['message' => 'Akun pada data belum lengkap'], 500);
            }

            $jurnal=[
                'company_id' => $ct->company_id,
                'date_transaction' => date('Y-m-d'),
                'created_by' => auth()->id(),
                'code' => $ct->code,
                'description' => $ct->description,
                'debet' => 0,
                'credit' => 0,
                'type_transaction_id' => $ct->type_transaction_id
            ];

            $j = Journal::create($jurnal);

            $amount=0;
            foreach ($details as $value) {
                JournalDetail::create([
                    'header_id' => $j->id,
                    'account_id' => $value->account_id,
                    'debet' => $value->amount,
                    'credit' => 0,
                    'description' => $value->description,
                ]);
                $amount+=$value->amount;
            }

            JournalDetail::create([
                'header_id' => $j->id,
                'account_id' => $ct->account_id,
                'debet' => 0,
                'credit' => $amount,
                'description' => $ct->description
            ]);


            DB::table('cash_transactions')
            ->whereId($id)
            ->update([
                'status_cost' => 3,
                'is_cut' => 1,
                'journal_id' => $j->id
            ]);

            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg; 

        return Response::json($data, $status_code);
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:image/jpeg,image/png,application/pdf',
        ],[
            'file.mimetypes' => 'File Harus Berupa Gambar atau PDF!',
            'file.required' => 'File belum ada!'
        ]);

        $file=$request->file('file');
        //var_dump($file);
        $file_name="BTK_".time()."_".$file->getClientOriginalName();

        $file->move(public_path('files'),$file_name);

        return Response::json(['file' => $file_name]);
    }

    public function deleteBukti(Request $request)
    {
      $request->validate([
        'filename' => 'required'
      ]);

      $file = "files/{$request->filename}";

      try{
        unlink($file);
      } catch(Exception $e) {
        return Response::json(['status'=>'error','message'=>$e->getMessage()]);
      }

      return Response::json(['status'=>'ok']);
    }

    public function delete_detail($id)
    {
        DB::beginTransaction();
            CashTransactionDetail::find($id)->delete();
        DB::commit();
        return Response::json(null);
    }
}

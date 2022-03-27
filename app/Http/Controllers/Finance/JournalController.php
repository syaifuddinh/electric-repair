<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Company;
use App\Model\TypeTransaction;
use App\Model\Account;
use App\Model\CashCategory;
use App\Model\JournalFavorite;
use App\Utils\TransactionCode;
use Response;
use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Journal AS J;
use App\Abstracts\Finance\Closing AS C;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['type_transaction']=TypeTransaction::all();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
      $data['type_transaction']=TypeTransaction::all();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function save($request) {
        DB::beginTransaction();
        $tp=TypeTransaction::find($request->type_transaction_id);
        $code = new TransactionCode($request->company_id, $tp->slug);
        $code->setCode();
        $trx_code = $code->getCode();

        $j=Journal::create([
          'company_id' => $request->company_id,
          'type_transaction_id' => $request->type_transaction_id,
          'date_transaction' => dateDB($request->date_transaction),
          'created_by' => auth()->id(),
          'code' => $trx_code,
          'description' => $request->description,
          'debet' => array_sum($request->debet),
          'credit' => array_sum($request->credit),
          'source' => 1,
          'is_audit' => $request->is_audit ?? 0
        ]);

        foreach ($request->account_id as $key => $value) {
          if ($value['type']['id']==1) {
            if (isset($request->cash_category_id[$key])) {
              $cc=CashCategory::find($request->cash_category_id[$key]);
            } else {
              $cc=null;
            }
            if (empty($cc)) {
              $cid=null;
            } else {
              $cid=$cc->id;
            }
          } else {
            $cid=null;
          }
          $cekCC=cekCashCount($request->company_id,$value['id']);
          if ($cekCC) {
            return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
          }

          JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $value['id'],
            'cash_category_id' => $cid,
            'debet' => $request->debet[$key] ?? 0,
            'credit' => $request->credit[$key] ?? 0,
            'description' => @$request->keterangan[$key]
          ]);
        }
        DB::commit();

        return $j->id;
    }

    public function store(Request $request)
    {
        $request->validate([
          'date_transaction' => 'required',
          'type_transaction_id' => 'required',
          'company_id' => 'required'
        ]);
        try {
            $this->save($request);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 421);            
        }

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
      $data['type_transaction']=TypeTransaction::all();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();
 
      $data['item']=Journal::with('company','type_transaction')->where('id', $id)->first();
      $data['detail']=JournalDetail::with('account')->where('header_id', $id)->orderBy('debet','desc')->get();
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
      $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
      $data['type_transaction']=TypeTransaction::all();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();
      $data['item']=Journal::with('details','details.account','details.account.parent','details.account.type')->where('id', $id)->first();
      if (!in_array($data['item']->source,[1,2])) {
        return Response::json(null,500);
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
    public function update(Request $request, $id)
    {
            $request->validate([
                'date_transaction' => 'required',
                'type_transaction_id' => 'required',
                'company_id' => 'required'
            ]);

            DB::beginTransaction();

            C::preventByDate(dateDB($request->date_transaction));

      JournalDetail::where('header_id', $id)->delete();
      foreach ($request->account_id as $key => $value) {
        $acc=Account::find($value);
        if ($acc->type->id==1) {
          if (isset($request->cash_category_id[$key])) {
            $cc=CashCategory::find($request->cash_category_id[$key]);
          } else {
            $cc=null;
          }
          if (empty($cc)) {
            $cid=null;
          } else {
            $cid=$cc->id;
          }
        } else {
          $cid=null;
        }

        $cekCC=cekCashCount($request->company_id,$value);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $id,
          'account_id' => $value,
          'cash_category_id' => $cid,
          'debet' => $request->debet[$key],
          'credit' => $request->credit[$key],
        ]);
      }
      $j=Journal::find($id)->update([
        'date_transaction' => dateDB($request->date_transaction),
        'description' => $request->description,
      ]);
      DB::commit();

      return Response::json(null);
    }

    /*
      Date : 29-08-2020
      Description : Hapus data
      Developer : Didin
      Status : Edit
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 29-08-2021
      Description : Menyetujui jurnal
      Developer : Didin
      Status : Create
    */
    public function approve(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::approve(auth()->id(), $request->item);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 29-08-2021
      Description : Membatalkan persetujuan jurnal
      Developer : Didin
      Status : Create
    */
    public function undo_approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::undoApprove($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 29-08-2021
      Description : posting jurnal, untuk memasukkan jurnal ke pembukuan
      Developer : Didin
      Status : Edit
    */
    public function approvePost(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::approvePost(auth()->id(), $request->item);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function create_favorite()
    {
      $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
      $data['type_transaction']=TypeTransaction::all();
      $data['company']=companyAdmin(auth()->id());
      $data['favorite']=JournalFavorite::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_favorite(Request $request)
    {
      $request->validate([
        'date_transaction' => 'required',
        'type_transaction_id' => 'required',
        'company_id' => 'required',
        'nominal' => 'required',
        'transaksi_favorit' => 'required'
      ]);
      // dd($request);
      DB::beginTransaction();
      $tp=TypeTransaction::find($request->type_transaction_id);
      $code = new TransactionCode($request->company_id, $tp->slug);
      $code->setCode();
      $trx_code = $code->getCode();

      $j=Journal::create([
        'company_id' => $request->company_id,
        'type_transaction_id' => $request->type_transaction_id,
        'date_transaction' => dateDB($request->date_transaction),
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'description' => $request->description,
        'debet' => $request->nominal,
        'credit' => $request->nominal,
        'source' => 2
      ]);

      $trx_fav=JournalFavorite::find($request->transaksi_favorit);
      foreach ($trx_fav->details as $key => $value) {
        if ($value->jenis==1) {
          $db=$request->nominal;
          $cr=0;
        } else {
          $db=0;
          $cr=$request->nominal;
        }

        $cekCC=cekCashCount($request->company_id,$value->account_id);
        if ($cekCC) {
          return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $value->account_id,
          'cash_category_id' => $value->cash_category_id,
          'debet' => $db,
          'credit' => $cr,
          'description' => $request->description
        ]);
      }
      DB::commit();

      return Response::json(null);
    }

    public function posting_rev($id)
    {
      $data['item']=Journal::with('type_transaction')->where('id', $id)->first();
      $data['detail']=JournalDetail::with('account')->where('header_id', $id)->orderBy('account_id','asc')->orderBy('debet','desc')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 29-08-2021
      Description : Membatalkan posting
      Developer : Didin
      Status : Edit
    */
    public function unposting(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::unposting(auth()->id(), $request->unposting_reason, $request->id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 29-08-2021
      Description : posting jurnal, untuk memasukkan jurnal ke pembukuan
      Developer : Didin
      Status : Create
    */
    public function store_posting(Request $request)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            J::posting(auth()->id(), $request->detail, $request->journal_id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }
}

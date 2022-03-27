<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\CashMigration;
use App\Model\Account;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use App\Abstracts\Finance\CashMigration AS CM;

class CashMigrationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['company']=companyAdmin(auth()->id());
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['company_to']=Company::all();
      $data['account']=Account::with('parent')->where('is_base',0)->whereIn('no_cash_bank',[1,2])->get();
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
        'date_request' => 'required',
        'date_needed' => 'required',
        'company_from' => 'required',
        'company_to' => 'required',
        'cash_account_from' => 'required',
        'cash_account_to' => 'required',
        'total' => 'required|min:1',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_from, 'cashMigration');
      $code->setCode();
      $trx_code = $code->getCode();

      CashMigration::create([
        'code' => $trx_code,
        'company_from' => $request->company_from,
        'company_to' => $request->company_to,
        'date_request' => dateDB($request->date_request),
        'date_needed' => dateDB($request->date_needed),
        'cash_account_from' => $request->cash_account_from,
        'cash_account_to' => $request->cash_account_to,
        'total' => $request->total,
        'description' => $request->description,
        'create_by' => auth()->id(),
      ]);
      DB::commit();

      return Response::json();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=CashMigration::with('company_fr','company_tr','account_from','account_to')->where('id', $id)->first();
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
      $data['item']=CashMigration::where('id', $id)->first();
      if ($data['item']->status>1) {
        return Response::json(['message' => 'Data Sudah melewati persetujuan!'],404);
      }
      $data['company']=companyAdmin(auth()->id());
      $data['company_to']=Company::all();
      $data['account']=Account::with('parent')->where('is_base',0)->whereIn('no_cash_bank',[1,2])->get();
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
        'date_request' => 'required',
        'date_needed' => 'required',
        'company_from' => 'required',
        'company_to' => 'required',
        'cash_account_from' => 'required',
        'cash_account_to' => 'required',
        'total' => 'required|min:1',
      ]);
      DB::beginTransaction();

      CashMigration::find($id)->update([
        'company_from' => $request->company_from,
        'company_to' => $request->company_to,
        'date_request' => dateDB($request->date_request),
        'date_needed' => dateDB($request->date_needed),
        'cash_account_from' => $request->cash_account_from,
        'cash_account_to' => $request->cash_account_to,
        'total' => $request->total,
        'description' => $request->description,
      ]);
      DB::commit();

      return Response::json();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            CM::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    
    public function reject(Request $request, $id)
    {
        DB::beginTransaction();
        CashMigration::find($id)
            ->update([
                'status' => 5,
                'reject_reason' => $request->input('reject_reason'),
                'approve_by' => auth()->id()
            ]);
        
        DB::commit();
    }


    public function approve($id)
    {
      DB::beginTransaction();
      CashMigration::find($id)->update([
        'status' => 2,
        'approve_by' => auth()->id(),
      ]);
      DB::commit();
    }
    public function approve_direction($id)
    {
      DB::beginTransaction();
      CashMigration::find($id)->update([
        'status' => 3,
        'approve_direction_by' => auth()->id(),
      ]);
      DB::commit();
    }

    public function realisation($id)
    {
      DB::beginTransaction();
      $item=CashMigration::find($id);
      //cabang asal
      $fr=DB::table('companies')->where('id', $item->company_from)->first();
      $to=DB::table('companies')->where('id', $item->company_to)->first();
      $jfr=Journal::create([
        'company_id' => $item->company_from,
        'type_transaction_id' => 44, //invoice
        'date_transaction' => Carbon::now(),
        'created_by' => auth()->id(),
        'code' => $item->code,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
        'status' => 2
      ]);
      if (!$fr->mutation_account_id) {
        return Response::json(['message' => 'Akun mutasi kas/bank pada cabang '.$fr->name.' belum ditentukan!'],500,[],JSON_NUMERIC_CHECK);
      }
      JournalDetail::create([
        'header_id' => $jfr->id,
        'account_id' => $fr->mutation_account_id,
        'debet' => $item->total,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
      ]);
      JournalDetail::create([
        'header_id' => $jfr->id,
        'account_id' => $item->cash_account_from,
        'credit' => $item->total,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
      ]);
      //cabang tujuan
      $jtr=Journal::create([
        'company_id' => $item->company_to,
        'type_transaction_id' => 44, //invoice
        'date_transaction' => Carbon::now(),
        'created_by' => auth()->id(),
        'code' => $item->code,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
        'status' => 2
      ]);
      if (!$to->mutation_account_id) {
        return Response::json(['message' => 'Akun mutasi kas/bank pada cabang '.$to->name.' belum ditentukan!'],500,[],JSON_NUMERIC_CHECK);
      }

      JournalDetail::create([
        'header_id' => $jtr->id,
        'account_id' => $to->mutation_account_id,
        'credit' => $item->total,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
      ]);
      JournalDetail::create([
        'header_id' => $jtr->id,
        'account_id' => $item->cash_account_to,
        'debet' => $item->total,
        'description' => 'Mutasi Kas dari '.$fr->name.' ke '.$to->name,
      ]);
      //ed jurnal --------------------
      $item->update([
        'status' => 4,
        'realisation_by' => auth()->id(),
        'date_realisation' => Carbon::now()
      ]);

      DB::commit();
    }
}

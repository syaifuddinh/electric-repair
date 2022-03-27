<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\NotaCredit;
use App\Model\Company;
use App\Model\Account;
use App\Model\Contact;
use App\Model\Receivable;
use App\Model\ReceivableDetail;
use App\Model\CashTransaction;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\TypeTransaction;
use App\Model\AccountDefault;
use App\Utils\TransactionCode;
use Response;
use DB;

class NotaCreditController extends Controller
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
        $data['company']=Company::all();
        $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
        $data['contact']=Contact::where('is_pelanggan', 1)->get();
        $data['cash_transaction']=CashTransaction::where('jenis', 1)->where('is_cut', 0)->get();

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
            'company_id' => 'required',
            'date_transaction' => 'required',
            'amount' => 'required|integer',
            'contact_id' => 'required',
            'receivable_id' => 'required',
            'account_id' => 'required',
            'contra' => 'required',
            'cash_transaction' =>'required_if:contra,1'
        ]);

        DB::beginTransaction();
        $code = new TransactionCode($request->company_id, "notaCredit");
        $code->setCode();
        $trx_code = $code->getCode();
        $tptrx=TypeTransaction::where('slug','notaCredit')->first();
        $i=NotaCredit::create([
            'company_id' => $request->company_id,
            'receivable_id' => $request->receivable_id,
            'contact_id' => $request->contact_id,
            'date_transaction' => dateDB($request->date_transaction),
            'jenis' => $request->jenis,
            'code' => $trx_code,
            'amount' => $request->amount,
            'reff_no' => (isset($request->cash_transaction)?$request->cash_transaction['code']:null),
            'description' => $request->description,
        ]);

        ReceivableDetail::create([
            'header_id' => $request->receivable_id,
            'type_transaction_id' => $tptrx->id,
            'relation_id' => $i->id,
            'code' => $trx_code,
            'date_transaction' => dateDB($request->date_transaction),
            'debet' => ($request->jenis==1?$request->amount:0),
            'credit' => ($request->jenis==2?$request->amount:0),
            'description' => $request->description,
        ]);

        if (isset($request->cash_transaction)) {
            CashTransaction::find($request->cash_transaction['id'])->update(['is_cut' => 1]);
        }

        $account_default=AccountDefault::first();
        $contact=Contact::find($request->contact_id);
        if (empty($contact->akun_piutang)) {
            if (empty($account_default->piutang)) {
                return Response::json(['message' => 'Akun Piutang belum ditentukan'],500);
            } else {
                $piutang=$account_default->piutang;
            }
        } else {
            $piutang=$contact->akun_piutang;
        }
        $j=Journal::create([
            'company_id' => $request->company_id,
            'type_transaction_id' => $tptrx->id,
            'date_transaction' => dateDB($request->date_transaction),
            'created_by' => auth()->id(),
            'code' => $trx_code,
            'description' => $request->description,
            'status' => 2
        ]);

        $cekCC=cekCashCount($request->company_id,($request->jenis==1?$piutang:$request->account_id));
        if ($cekCC) {
            return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => ($request->jenis==1?$piutang:$request->account_id),
            'debet' => $request->amount,
            'credit' => 0,
        ]);

        $cekCC=cekCashCount($request->company_id,($request->jenis==2?$piutang:$request->account_id));
        if ($cekCC) {
            return Response::json(['message' => 'Akun yang anda masukkan sementara dibekukan dikarenakan sedang dalam perhitungan fisik kas'],500);
        }

        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => ($request->jenis==2?$piutang:$request->account_id),
            'debet' => 0,
            'credit' => $request->amount,
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
        $data['item']=NotaCredit::with('company','contact','receivable')->where('id', $id)->first();
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
        DB::beginTransaction();
        $notaCredit = NotaCredit::find($id);
        if($notaCredit->reff_no){
            CashTransaction::where('code',$notaCredit->reff_no)->update(['is_cut'=>0]);
        }
        $journal = Journal::where('code',$notaCredit->code)->first();
        foreach($journal->details as $dt){
            $dt->delete();
        }
        $journal->delete();
        $notaCredit->detailReceivable->delete();
        $notaCredit->receivable->debet = $notaCredit->receivable->details->sum('debet');
        $notaCredit->receivable->credit = $notaCredit->receivable->details->sum('credit');
        $notaCredit->receivable->save();
        $notaCredit->delete();
        DB::commit();
        return Response::json(null);

    }

    public function cari_piutang($id)
    {
        $c=Receivable::where('contact_id', $id)->whereRaw('debet - credit > 0')->get();
        return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }
}

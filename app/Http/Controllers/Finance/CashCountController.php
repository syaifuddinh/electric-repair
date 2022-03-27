<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Account;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\CashCount;
use App\Model\CashCountDetail;
use DB;
use Response;
use DateTime;
use Carbon\Carbon;

class CashCountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['company'] = companyAdmin(auth()->id());
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function approve(Request $request, $id) {
      DB::beginTransaction();
      $users = DB::table('users')->where('username', $request->username)->where('pass_text', $request->password);
      if($users->count() == 0) {
        return Response::json(['message' => 'Username atau password tidak ditemukan'],500);
      }
      else {
        $roles = DB::table('roles')->where('slug', 'finance.cash_count.validation' )->first();
        $role_id = $roles->id;
        $user_roles = DB::table('user_roles')->where('user_id', $users->first()->id)->where('role_id', $role_id);
        if($user_roles->count() == 0) {
          return Response::json(['message' => 'Anda tidak diijinkan untuk meng-approve cash count'],500);
        }
        else {
          $c = CashCount::find($id);
          $c->status = 1;
          $c->approved_by_id = $users->first()->id;

          $c->save();
        }
      }

      DB::commit();

      return Response::json(null);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
      $today = date_create();
      date_sub($today, date_interval_create_from_date_string('1 days'));
      $yesterday = date_format($today, 'Y-m-d');

      $where = '';
      if(isset($request->company_id)) {
        $where .= ' AND company_id = ' . $request->company_id;
      }

      $yesterday=Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
      // dd($yesterday);
      $data['company']=companyAdmin(auth()->id());
      $data['saldo_awal'] = DB::select("SELECT IFNULL(sum(saldo_awal), 0) AS saldo_awal FROM view_cash_ammounts WHERE date_transaction = '$yesterday'" . $where)[0]->saldo_awal;
      $data['bkk_hari_ini'] = DB::select("SELECT IFNULL(sum(credit), 0) AS credit FROM view_cash_ammounts WHERE date_transaction = DATE_FORMAT(NOW(), '%Y-%m-%d')" . $where)[0]->credit;
      $data['bkm_hari_ini'] = DB::select("SELECT IFNULL(sum(debet), 0) AS debet FROM view_cash_ammounts WHERE date_transaction = DATE_FORMAT(NOW(), '%Y-%m-%d')" . $where)[0]->debet;
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
        'date_transaction' => 'required',
        // 'officer' => 'required',
        'saldo_awal' => 'required',
        'bailout' => 'required',
        'total_saldo' => 'required',
      ]);
      DB::beginTransaction();
      $cek=CashCount::whereRaw("date_transaction = ".dateDB($request->date_transaction)." and company_id = $request->company_id")->first();
      if (isset($cek)) {
        return Response::json(['message' => 'Penghitungan Kas Tgl Tersebut sudah dilakukan!'],500);
      }
      if ($request->saldo_akhir!=$request->total_saldo + $request->total_kasbon) {
        return Response::json(['message' => 'Saldo dengan Perhitungan tidak sesuai!'],500);
      }
      $l=CashCount::create([
        'company_id' => $request->company_id,
        'saldo_awal' => $request->saldo_awal,
        'bkk_hari_ini' => $request->bkk_hari_ini,
        'bkm_hari_ini' => $request->bkm_hari_ini,
        'saldo_akhir' => $request->saldo_akhir,
        'total_cash_fisik' => $request->total_saldo,
        'total_kasbon' => $request->total_kasbon,
        'bailout' => $request->bailout,
        'officer' => auth()->id(),
        'description' => $request->description,
        'date_transaction' => dateDB($request->date_transaction),
        'create_by' => auth()->id(),
      ]);
      foreach ($request->detail as $key => $value) {
        CashCountDetail::create([
          'header_id' => $l->id,
          'nominal' => $value['value'] ?? 0,
          'qty' => $value['amount'] ?? 0,
          'total' => $value['total'] ?? 0,
        ]);
      }
      foreach ($request->cash_advance as $key => $value) {
        DB::table('cash_advance_reports')->insert([
          'cash_count_id' => $l->id,
          'cash_advance_id' => $value['id']
        ]);
      }
      // Account::whereRaw("company_id = $request->company_id")->update([
      //   'is_freeze' => 0
      // ]);
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
      $item=CashCount::with('company')->leftJoin('users AS S2', 'S2.id', '=', 'officer')->where('cash_counts.id', $id)->selectRaw('cash_counts.*, S2.name AS officer_name');
      if($item->first()->status == 1) {
        $item = $item->leftJoin('users', 'approved_by_id', 'users.id')->selectRaw(' users.name AS approved_by_name');
      }

      $data['item']=$item->first();


      $data['detail']=CashCountDetail::where('header_id', $id)->orderBy('nominal','desc')->get();

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
        //
    }

    public function cari_saldo($cid)
    {
      $acc=DB::table('accounts')
      ->whereRaw("accounts.company_id = $cid")
      ->where('accounts.no_cash_bank', 1)
      ->where('accounts.is_cash_count', 1)
      ->selectRaw('accounts.name, accounts.jenis,accounts.id,accounts.is_freeze')->get();
      $saldo=0;
      $is_freeze=false;
      // dd($acc);
      $yesterday=Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
      foreach ($acc as $key => $value) {
        $dt=DB::select("select sum(journal_details.debet) as db, sum(journal_details.credit) as cr from journal_details left join journals on journals.id = journal_details.header_id where journals.status = 3 and journal_details.account_id = $value->id and date_transaction <= '$yesterday'");
        // dd($dt);
        // $cr=JournalDetail::where('account_id', $value->id)->sum('credit');
        foreach ($dt as $vls) {
          if ($value->jenis==1) {
            $saldo+=($vls->db-$vls->cr);
          } else {
            $saldo+=($vls->cr-$vls->db);
          }
        }
        // dd($value);
        if ($value->is_freeze==1) {
          $is_freeze=true;
        }
      }
      return Response::json(['saldo' => $saldo,'is_freeze' => $is_freeze]);
    }

    public function toggle_freeze(Request $request, $cid)
    {
      // dd($request->freeze);
      DB::beginTransaction();
      $acc=Account::whereRaw("company_id = $cid and no_cash_bank = 1")->get();
      foreach ($acc as $key => $value) {
        if ($request->freeze==true) {
          $value->update([
            'is_freeze' => 0
          ]);
        } else {
          $value->update([
            'is_freeze' => 1
          ]);
        }
      }
      DB::commit();

      if ($request->freeze==true) {
        $message="Akun Berhasil Dilepaskan !";
      } else {
        $message="Akun Berhasil Dibekukan !";
      }
      return Response::json(['message' => $message]);
    }
}

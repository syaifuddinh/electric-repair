<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\TypeTransaction;
use App\Model\Account;
use App\Model\CashCategory;
use App\Model\JournalFavorite;
use App\Model\JournalFavoriteDetail;
use Response;
use DB;

class FavoriteController extends Controller
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
      $data['account']=Account::with('parent','type')->where('is_base',0)->orderBy('code')->get();
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();

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
        'name' => 'required',
        'account_id' => 'required|array'
      ], [
        'name.required' => 'Judul Transaksi Favorit wajib diisi',
        'account_id.required' => 'Detail Jurnal/Akun wajib diisi'
      ]);
      // dd($request);
      DB::beginTransaction();
      $h=JournalFavorite::create([
        'name' => $request->name,
        'created_by' => auth()->id(),
      ]);
      $db=0;
      $cr=0;
      $counter=0;
      foreach ($request->account_id as $key => $value) {
        if (empty($value)) {
          continue;
        } else {
          $counter++;
        }
        if ($value['type']['id']==1 && empty($request->cash_category_id[$key])) {
          return Response::json(['message' => 'Kategori Kas Tidak boleh kosong jika akun bertipe Kas'],500);
        }
        if ($request->jenis[$key]==1) {
          $db++;
        } else {
          $cr++;
        }
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
        JournalFavoriteDetail::create([
          'header_id' => $h->id,
          'account_id' => $value['id'],
          'cash_category_id' => $cid,
          'jenis' => $request->jenis[$key]
        ]);
      }
      if ($cr==0 || $db==0) {
        return Response::json(['message' => 'Harus ada salah satu akun di posisi Debet/Kredit'],500);
      }
      if ($counter<2) {
        return Response::json(['message' => 'Jumlah Akun harus lebih dari 1'],500);
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
    public function show($id)
    {
        //
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
      $data['cash_category']=CashCategory::with('category')->where('is_base', 0)->get();
      $data['item']=JournalFavorite::with('details')->where('id', $id)->first();
      // dd($data);
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
      // dd($request);
      $request->validate([
        'name' => 'required'
      ]);

      DB::beginTransaction();
      JournalFavorite::find($id)->update([
        'name' => $request->name,
        // 'created_by' => auth()->id(),
      ]);
      $h=JournalFavorite::find($id);
      JournalFavoriteDetail::where('header_id', $id)->delete();

      $db=0;
      $cr=0;
      $counter=0;
      if (empty($request->account_id)) {
        return Response::json(['message' => 'Tidak ada akun yang dipilih'],500);
      }
      foreach ($request->account_id as $key => $value) {
        if (empty($value)) {
          continue;
        } else {
          $counter++;
        }
        $acc=Account::find($value);
        if ($acc->type->id==1 && empty($request->cash_category_id[$key])) {
          return Response::json(['message' => 'Kategori Kas Tidak boleh kosong jika akun bertipe Kas'],500);
        }
        if ($request->jenis[$key]==1) {
          $db++;
        } else {
          $cr++;
        }
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
        JournalFavoriteDetail::create([
          'header_id' => $h->id,
          'account_id' => $value,
          'cash_category_id' => $cid,
          'jenis' => $request->jenis[$key]
        ]);
      }
      if ($cr==0 || $db==0) {
        return Response::json(['message' => 'Harus ada salah satu akun di posisi Debet/Kredit'],500);
      }
      if ($counter<2) {
        return Response::json(['message' => 'Jumlah Akun harus lebih dari 1'],500);
      }
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
        JournalFavorite::find($id)->delete();
        DB::commit();
        return Response::json(null);
    }
}

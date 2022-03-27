<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CashCategory;
use App\Model\CashCategoryDetail;
use App\Model\Account;
use App\Model\TypeTransaction;
use Response;
use DB;

class CashCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['parent']=CashCategory::where('is_base', 1)->orderBy('code')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        'code' => 'required|unique:cash_categories',
        'name' => 'required',
        'parent_id' => 'required',
      ]);
      DB::beginTransaction();
      CashCategory::create([
        'code' => $request->code,
        'name' => $request->name,
        'jenis' => $request->jenis,
        'kategori' => $request->parent_id,
        'parent_id' => $request->parent_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function store_detail(Request $request, $id)
    {
      // dd($request);
      DB::beginTransaction();
      $head=CashCategory::find($id);
      $cek=DB::select("SELECT count(*) as tot FROM cash_category_details LEFT JOIN cash_categories ON cash_categories.id = cash_category_details.header_id WHERE cash_category_details.type_transaction_id = $request->type_transaction_id AND cash_categories.jenis = $head->jenis")[0];
      if ($cek->tot>0) {
        return Response::json(['message' => 'Tipe Transaksi ini sudah digunakan di kategori kas lainnya.'],500);
      }
      CashCategoryDetail::create([
        'header_id' => $id,
        'type_transaction_id' => $request->type_transaction_id,
        'description' => $request->description,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      CashCategoryDetail::find($id)->delete();
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
      $data['item']=CashCategory::with('category','details','details.type_transaction')->where('id', $id)->first();
      $data['type_transaction']=TypeTransaction::all();

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
      $data=CashCategory::find($id);
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
        'code' => 'required|unique:cash_categories,code,'.$id,
        'name' => 'required',
        'parent_id' => 'required',
      ]);

      DB::beginTransaction();
      CashCategory::find($id)->update([
        'code' => $request->code,
        'name' => $request->name,
        'jenis' => $request->jenis,
        'kategori' => $request->parent_id,
        'parent_id' => $request->parent_id,
      ]);
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
      CashCategory::find($id)->delete();
      return Response::json(null);
    }
}

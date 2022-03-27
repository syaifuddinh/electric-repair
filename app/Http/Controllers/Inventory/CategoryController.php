<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Abstracts\Inventory\ItemCategory;
use Response;
use DB;

class CategoryController extends Controller
{
    /*
      Date : 29-08-2020
      Description : Menampilkan kategori barang
      Developer : Didin
      Status : Create
    */
    public function index(Request $request)
    {
        $dt = DB::table('categories')->whereRaw("parent_id is not null and ban_master = 0");

        if($request->filled('is_container_part')) {
            $dt = $dt->where('categories.is_container_part', $request->is_container_part);
        } 

        if($request->filled('is_container_yard')) {
            $dt = $dt->where('categories.is_container_yard', $request->is_container_yard);
        } 

        if($request->filled('is_pallet')) {
            $dt = $dt->where('categories.is_pallet', $request->is_pallet);
        } 

        $dt = $dt->selectRaw('id,parent_id,code,name,is_tire,is_asset,is_jasa,is_ban_luar,is_ban_dalam,is_marset,ban_master,is_pallet')->get();
        $data['data'] = $dt;
        $data['message'] = 'OK';

        return response()->json($dt);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['parent']=Category::where('parent_id', null)->where('ban_master',0)->get();

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
        'code' => 'required|unique:categories,code',
        'name' => 'required',
      ]);

      DB::beginTransaction();
      ItemCategory::store($request->all());
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
      $dt = ItemCategory::show($id);
      return response()->json($dt);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['parent'] = Category::whereRaw("parent_id is null")->get();
        $data['category'] = Category::find($id);
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
            'code' => 'required|unique:categories,code,'.$id,
            'name' => 'required',
        ]);

        DB::beginTransaction();
        ItemCategory::update($request->all(), $id);
        DB::commit();

        return Response::json(null);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        DB::table('categories')->where('id', $id)->delete();
        DB::commit();
        return Response::json(['message' => 'OK']);
    }
}

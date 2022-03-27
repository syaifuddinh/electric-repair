<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\City;
use App\Model\Province;
use Response;
use DB;
use Exception;

class CityController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index()
    {
        $dt = DB::table('cities')
        ->select('id', 'name')
        ->get();

        return response()->json(['message' => 'OK', 'data' => $dt]);
    }
    
    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create()
    {
        $data['country']=DB::table('countries')->select('id', 'name')->get();
        $data['province']=Province::with('country')->get();
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
        DB::beginTransaction();
        City::create([
        'code' => $request->code,
        'province_id' => $request->province_id,
        'type' => $request->type,
        'name' => $request->name,
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
        $dt = DB::table('cities')
        ->whereId($id)
        ->first();
        if($dt) {
            return Response::json($dt, 200, [], JSON_NUMERIC_CHECK);
        } else {
            return Response::json(['message' => 'Data tidak ditemukan'], 404);
        }
    }
    
    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id)
    {
        $data['province']=Province::all();
        $data['item']=City::find($id);
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
        DB::beginTransaction();
        City::find($id)->update([
            'code' => $request->code,
            'province_id' => $request->province_id,
            'type' => $request->type,
            'name' => $request->name,
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
        $city = DB::table('cities')
        ->whereId($id)
        ->first();
        if($city !== null) {
            try {
                DB::table('cities')
                ->whereId($id)
                ->delete();
            } catch (Exception $e) {
                return Response::json(['message' => 'Data tidak bisa dihapus karena sudah digunakan'], 421);
            }
        } else {
            return Response::json(['message' => 'Data tidak ditemukan'], 404);
        }
    }
}
        
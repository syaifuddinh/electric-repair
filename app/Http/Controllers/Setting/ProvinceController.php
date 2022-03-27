<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Exception;

class ProvinceController extends Controller
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
        $request->validate([
            'country_id' => 'required',
            'name' => 'required'
        ], [
            'country_id.required' => 'Country name is required'
        ]);
        DB::beginTransaction();
        DB::table('provinces')
        ->insert([
            'name' => $request->name,
            'country_id' => $request->country_id
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
        $dt = DB::table('provinces')
        ->whereId($id)
        ->first();

        return Response::json($dt);
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
        $request->validate([
            'country_id' => 'required',
            'name' => 'required'
        ], [
            'country_id.required' => 'Country name is required'
        ]);
        DB::beginTransaction();
        try {
            $exist = DB::table('provinces')
            ->whereId($id)
            ->count('id');
            if($exist == 0) {
                throw new Exception('Data tidak ditemukan');
            }
            DB::table('provinces')
            ->whereId($id)
            ->update([
                'name' => $request->name,
                'country_id' => $request->country_id
            ]);
            DB::commit();
        } catch(Exception $e) {
            return response()->json(['message' => $e->getMessage()], 421);
        }
        
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
        $province = DB::table('provinces')
        ->whereId($id)
        ->first();
        if($province !== null) {
            try {
                DB::table('provinces')
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
        
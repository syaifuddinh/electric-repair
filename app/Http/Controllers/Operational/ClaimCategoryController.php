<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClaimCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::table('claim_categories')
                    ->select('id', 'name')
                    ->get();
        return response()->json($data);
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
            'name' => 'required|unique:claim_categories,name'
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'name.unique' => 'Nama harus unik'
        ]);

        DB::table('claim_categories')
        ->insert([
            'name' => $request->name,
            'created_at' => Carbon::now('Asia/Jakarta')
        ]);

        return response()->json(['message' => 'Data berhasil disimpan']);
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
        $request->validate([
            'name' => 'required|unique:claim_categories,name,'.$id
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'name.unique' => 'Nama harus unik'
        ]);

        DB::table('claim_categories')
        ->whereId($id)
        ->update([
            'name' => $request->name,
            'updated_at' => Carbon::now('Asia/Jakarta')
        ]);

        return response()->json(['message' => 'Data berhasil disimpan']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $exist = DB::table('claim_categories')
        ->whereId($id)
        ->count('id');

        if($exist > 0) {
            DB::table('claim_categories')
            ->whereId($id)
            ->delete();
        } else {
            return response()->json(['message' => 'Data tidak ditemukan'], 421);
        }

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}

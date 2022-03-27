<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\TypeTransaction;
use Response;
use DataTables;
use DB;

class TransactionLockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      // dd($request)
      $item = TypeTransaction::query();

      return DataTables::of($item)
      ->editColumn('last_date_lock', function($item){
        return !empty($item->last_date_lock) ? $item->last_date_lock : 'Belum disetting.';
      })
      ->addColumn('action', function($item){
        $html="<a ng-click='editArea($item->id)'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
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

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $item = TypeTransaction::find($id);
      return $item;
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
      $item = TypeTransaction::find($id);
      $item->last_date_lock = dateDB($request->last_date_lock);

      $item->save();

      return Response::json([], 200);
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
}

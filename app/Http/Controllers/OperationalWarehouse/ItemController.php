<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Item;
use App\Model\Rack;
use App\Model\Category;
use App\Model\Contact;
use App\Model\Account;
use Response;
use DB;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['customers'] = Contact::where('is_pelanggan', 1)->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['customers'] = Contact::where('is_pelanggan', 1)->get();
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
        ]);;

        DB::beginTransaction();
         $volume = ($request->long ?? 0) + ($request->wide ?? 0) + ($request->height ?? 0);
        Item::create([
            'name' => $request->name,
            'long' => $request->long ?? 0,
            'wide' => $request->wide ?? 0,
            'height' => $request->height ?? 0,
            'volume' => $volume ?? 0,
            'tonase' => $request->tonase ?? 0
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
      $data['item'] = Item::find($id);
      $data['customers'] = Contact::where('is_pelanggan', 1)->get();
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
            'name' => 'required'
        ]);

        DB::beginTransaction();
        Item::find($id)->update([
            'name' => $request->name,
            'customer_id' => $request->customer_id,
            
            'long' => $request->long ?? 0,
            'wide' => $request->wide ?? 0,
            'height' => $request->height ?? 0,
            'volume' => $request->volume ?? 0,
            'tonase' => $request->tonase ?? 0
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
        DB::beginTransaction();
        Item::find($id)->update([
            'is_active' => 0
        ]);
        DB::commit();

        return Response::json(null);
    }
}

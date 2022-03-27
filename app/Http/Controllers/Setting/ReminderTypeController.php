<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\ReminderType;
use Response;
use DB;
use Yajra\DataTables\Facades\DataTables;

class ReminderTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $data['index']=ReminderType::all();

      if($request->dt && $request->dt == true){
        $reminder = ReminderType::query();

        return DataTables::of($reminder)
        ->addColumn('action', function($row){
          return [ 'id' => $row->id ];
        })
        ->make(true);
      }

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
            'name' => 'required',
            'type' => 'required',
            'interval' => 'required|min:1',
          ]);
          DB::beginTransaction();
          $slug = str_replace(' ', '', $request->name);
          ReminderType::create([
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'interval' => $request->interval,
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
      $data=ReminderType::find($id);
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
        'type' => 'required',
        'interval' => 'required|min:1',
      ]);
      DB::beginTransaction();
      ReminderType::find($id)->update([
        'type' => $request->type,
        'interval' => $request->interval,
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
        //
    }
}

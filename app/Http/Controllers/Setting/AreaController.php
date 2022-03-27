<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Area;
use DB;
use DataTables;
use Response;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['data'] = Area::all();
      return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

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
        Area::create(['name' => $request->name]);
        DB::commit();

        return response()->json(['message' => 'success']);
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
      $item = Area::find($id);
      return response()->json($item);
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
      Area::find($id)->update(['name' => $request->name]);
      DB::commit();

      return response()->json(['message' => 'success']);
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
        Area::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function datatable(Request $request)
    {
      $item = Area::query()
          ->when(!$request->order, function ($query) {
              $query->orderByDesc('created_at');
          });

      // dd($item);
//        return response()->json($request->all());
      return DataTables::of($item)
        ->addColumn('action', function($item){
          $html="<a ng-show='roleList.includes('setting.regional.edit')' ng-click='editArea({$item->id})' data-toggle='tooltip' title='Edit Area'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show='roleList.includes('setting.regional.delete')' ng-click='deletes($item->id)' data-toggle='tooltip' title='Delete Area'><span class='fa fa-trash-o'></span></a>";
          return $html;
        })
        ->rawColumns(['action'])
        ->make(true);
    }
}

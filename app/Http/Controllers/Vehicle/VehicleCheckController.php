<?php

namespace App\Http\Controllers\Vehicle;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Vehicle;
use App\Model\VehicleChecklist;
use App\Model\VehicleBody;
use App\Model\VehicleChecklistItem;
use App\Model\VehicleChecklistDetailItem;
use App\Model\VehicleChecklistDetailBody;
use App\Model\Company;
use DB;
use Response;

class VehicleCheckController extends Controller
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
        $data['company']=companyAdmin(auth()->id());
        $data['checklist']=VehicleChecklist::select('id','is_active','name')->get();
        $data['body']=VehicleBody::select('id','is_active','name')->get();
        // $data['vehicle']=Vehicle::all();

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
            'company_id' => 'required',
            'vehicle_id' => 'required',
            'officer' => 'required',
            'date_transaction' => 'required'
        ]);

        DB::beginTransaction();
        // SUM(is_exist + is_function + IF('condition' = 1, 1, 0)) FROM 'vehicle_checklist_detail_items' WHERE header_id = 2; contoh query

        $i=VehicleChecklistItem::create([
            'company_id' => $request->company_id,
            'vehicle_id' => $request->vehicle_id,
            'officer' => $request->officer,
            'date_transaction' => dateDB($request->date_transaction),
            'create_by' => auth()->id(),
            'score' => $request->score ?? 0,
        ]);

        $checkItemsHasIsExist = false;
        $checkBodysHasIsExist = false;

        if(is_array($request->items)) {
            foreach ($request->items as $key => $value) {
                VehicleChecklistDetailItem::create([
                    'header_id' => $i->id,
                    'vehicle_checklist_id' => $value['id'],
                    'is_exist' => $value['is_exist'],
                    'is_function' => $value['is_function'],
                    'condition' => $value['condition'],
                ]);

                if($value['is_exist'] == 1) {
                    $checkItemsHasIsExist = true;
                }
            }
        }

        foreach ($request->body as $key => $value) {
            VehicleChecklistDetailBody::create([
                'header_id' => $i->id,
                'vehicle_body_id' => $value['id'],
                'is_exist' => $value['is_exist'],
                'is_function' => $value['is_function'],
                'condition' => $value['condition'],
            ]);

            if($value['is_exist'] == 1) {
                $checkBodysHasIsExist = true;
            }
        }

        $vehicle_checklist_detail_item = DB::table('vehicle_checklist_detail_items')
        ->whereHeaderId($i->id)
        ->selectRaw('100 * SUM( IF(is_exist = 0, 0, is_exist + is_function + IF(`condition` = 1, 1, 0)) ) / (3 * IFNULL( (SELECT COUNT("id") FROM vehicle_checklists) , 0)) AS nilai')
        ->first();

        $nilai_body = DB::table('vehicle_checklist_detail_bodies')
        ->whereHeaderId($i->id)
        ->selectRaw('100 * SUM( IF(is_exist = 0, 0, is_exist + is_function + IF(`condition` = 1, 1, 0)) ) / (3 * IFNULL( (SELECT COUNT("id") FROM vehicle_bodies) , 0)) AS nilai')->first();
        $score = ($vehicle_checklist_detail_item->nilai + $nilai_body->nilai) / 2;
        $i->update(['score' => $score ?? 0 ]);

        if(!$checkItemsHasIsExist && !$checkBodysHasIsExist) {
            DB::rollback();
            return Response::json(['errors' => ['items_and_body' => 'The items or body checklist is required.'], 'message' => 'The given data was invalid.'], 422, [], JSON_NUMERIC_CHECK);
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
        $data['item']=VehicleChecklistItem::find($id);
        $data['checklist'] = VehicleChecklistDetailItem::where('header_id', $id)->get();
        $data['body'] = VehicleChecklistDetailBody::where('header_id', $id)->get();

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
        //
    }

    /*
      Date : 11-03-2020
      Description : Meng-update pengecekan kendaraan
      Developer : Didin
      Status : Edit
    */
    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'required',
            'officer' => 'required',
            'score' => 'required|numeric',
            'date_transaction' => 'required'
        ]);

        DB::beginTransaction();
        $i = VehicleChecklistItem::find($id);

        $i->update([
        'company_id' => $request->company_id,
        'vehicle_id' => $request->vehicle_id,
        'officer' => $request->officer,
        'date_transaction' => dateDB($request->date_transaction),
        'create_by' => auth()->id(),
        'score' => $request->score
      ]);

      $checkItemsHasIsExist = false;
      $checkBodysHasIsExist = false;

        foreach ($request->items as $key => $value) {

            if (array_key_exists('id', $value)) {
                $detailItem = VehicleChecklistDetailItem::find($value['id']);

                $detailItem->update([
                    'is_exist' => $value['is_exist'],
                    'is_function' => $value['is_function'],
                    'condition' => $value['condition']
                ]);
            } else {
                if($value['is_exist'] == 1 || $value['is_function'] == 1 || $value['condition'] == 1) {
                    VehicleChecklistDetailItem::create([
                        'header_id' => $id,
                        'vehicle_checklist_id' => $value['vehicle_checklist_id'],
                        'is_exist' => $value['is_exist'] ?? 0,
                        'is_function' => $value['is_function'] ?? 0,
                        'condition' => $value['condition'] ?? 0
                    ]);
                }
            }

            if($value['is_exist'] == 1) {
                $checkItemsHasIsExist = true;
            }
        }
        foreach ($request->body as $key => $value) {
            if(!empty($value)) {
                if (array_key_exists('id', $value)) {

                    $detailBody = VehicleChecklistDetailBody::find($value['id']);
                    $detailBody->update([
                        'is_exist' => $value['is_exist'],
                        'is_function' => $value['is_function'],
                        'condition' => $value['condition']
                    ]);
                } else {
                    if(array_key_exists('vehicle_body_id', $value)) {
                        VehicleChecklistDetailBody::create([
                            'header_id' => $id,
                            'vehicle_body_id' => $value['vehicle_body_id'],
                            'is_exist' => $value['is_exist'] ?? 0,
                            'is_function' => $value['is_function'] ?? 0,
                            'condition' => $value['condition'] ?? 0
                        ]);
                    }
                }
            }

            if($value['is_exist'] == 1) {
                $checkBodysHasIsExist = true;
            }
        }

        $vehicle_checklist_detail_item = DB::table('vehicle_checklist_detail_items')
        ->whereHeaderId($id)
        ->selectRaw('100 * SUM( IF(is_exist = 0, 0, is_exist + is_function + IF(`condition` = 1, 1, 0)) ) / (3 * IFNULL( (SELECT COUNT("id") FROM vehicle_checklists) , 0)) AS nilai')
        ->first();

        $nilai_body = DB::table('vehicle_checklist_detail_bodies')
        ->whereHeaderId($id)
        ->selectRaw('100 * SUM( IF(is_exist = 0, 0, is_exist + is_function + IF(`condition` = 1, 1, 0)) ) / (3 * IFNULL( (SELECT COUNT("id") FROM vehicle_bodies) , 0)) AS nilai')->first();
        $score = ($vehicle_checklist_detail_item->nilai + $nilai_body->nilai) / 2;
        $i->update(['score' => $score ]);

        if(!$checkItemsHasIsExist || !$checkBodysHasIsExist) {
            DB::rollback();
            return Response::json(['errors' => ['items_and_body' => 'The items & body checklist is required.'], 'message' => 'The given data was invalid.'], 422, [], JSON_NUMERIC_CHECK);
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
        VehicleChecklistDetailBody::where('header_id', $id)->delete();
        VehicleChecklistDetailItem::where('header_id', $id)->delete();
        VehicleChecklistItem::find($id)->delete();
    }
}

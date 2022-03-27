<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\AdditionalField;
use Response;
use DB;

class AdditionalFieldController extends Controller
{
    protected $table = 'additional_fields';

    public function indexGroup()
    {
        $dt = AdditionalField::indexGroup();
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function indexByTransaction($type_transaction) {
        $dt = AdditionalField::indexByTransaction($type_transaction);
        $data['message'] = 'OK';        
        $data['data'] = $dt;        

        return response()->json($data);
    }

    /*
      Date : 02-03-2021
      Description : Mendapatkan additional field yang masuk laporan job order summary
      Developer : Didin
      Status : Create
    */
    public function indexInJobOrderSummary() {
        $dt = AdditionalField::indexByTransaction('jobOrder', ['show_in_job_order_summary' => 1]);
        $data['message'] = 'OK';        
        $data['data'] = $dt;        

        return response()->json($data);
    }

    /*
      Date : 02-03-2021
      Description : Mendapatkan additional field yang masuk ke manifest
      Developer : Didin
      Status : Create
    */
    public function indexInManifest() {
        $dt = AdditionalField::indexByTransaction('jobOrder', ['show_in_manifest' => 1]);
        $data['message'] = 'OK';        
        $data['data'] = $dt;        

        return response()->json($data);
    }

    /*
      Date : 02-03-2021
      Description : Mendapatkan additional field yang masuk ke halaman index
      Developer : Didin
      Status : Create
    */
    public function indexInIndex($type_transaction) {
        $dt = AdditionalField::indexByTransaction($type_transaction, ['show_in_index' => 1]);
        $data['message'] = 'OK';        
        $data['data'] = $dt;        

        return response()->json($data);
    }

    /*
      Date : 02-03-2021
      Description : Mendapatkan additional field yang masuk laporan operational progress
      Developer : Didin
      Status : Create
    */
    public function indexInOperationalProgress() {
        $dt = AdditionalField::indexByTransaction('jobOrder', ['show_in_operational_progress' => 1]);
        $data['message'] = 'OK';        
        $data['data'] = $dt;        

        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['city']=City::join('provinces', 'provinces.id', 'cities.province_id')
      ->select('cities.*', 'provinces.name AS province_name', 'provinces.country_id')
      ->get();
      // $data['vehicle_type']=VehicleType::all();
      $data['company']=companyAdmin(auth()->id());

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan additional field
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $v = [];
        $v['name'] = 'required';
        $v['type_transaction_id'] = 'required';
        $v['field_type_id'] = 'required';
        $request->validate($v);

        DB::beginTransaction();
        $dt['message'] = 'Data successfully saved';
        $status_code = 200;
        try {
            AdditionalField::store($request->all());
        } catch (Exception $e) {
            $status_code = 421;
            $dt['message'] = $e->getMessage();
        }
        DB::commit();
        return Response::json($dt, $status_code);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $dt['message'] = 'OK';
        $status_code = 200;
        try {
            $dt['data'] = AdditionalField::show($id);
        } catch (Exception $e) {
            $status_code = 421;
            $dt['message'] = $e->getMessage();
        }
        DB::commit();
        return Response::json($dt, $status_code);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
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
        $v = [];
        $v['name'] = 'required';
        $v['type_transaction_id'] = 'required';
        $v['field_type_id'] = 'required';
        $request->validate($v);

        DB::beginTransaction();
        $dt['message'] = 'Data successfully saved';
        $status_code = 200;
        try {
            AdditionalField::update($request->all(), $id);
        } catch (Exception $e) {
            $status_code = 421;
            $dt['message'] = $e->getMessage();
        }
        DB::commit();
        return Response::json($dt, $status_code);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status_code = 200;
        try {
            $dt = AdditionalField::destroy($id);
            $msg = 'Data successfully deleted';
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
            $dt = (object) [];
        }

        $data['message'] = $msg;
        $data['data'] = $dt;

        return response()->json($data, $status_code);
    }
}

<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Rack;
use App\Model\StorageType;
use App\Model\Area;
use App\Model\City;
use App\Model\Company;
use App\Model\TypeTransaction;
use App\Model\CompanyNumbering;
use App\Model\Warehouse;
use App\Model\WarehouseType;
use App\Model\Account;
use Response;
use DB;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = companyAdmin(auth()->id());
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 31-03-2020
      Description : Menampilkan daftar cabang
      Developer : Didin
      Status : Create
    */
    public function create()
    {
        $data['company'] = companyAdmin(auth()->id());
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }


    public function store(Request $request)
    {
      // dd($request);
      $request->validate([
        'code' => 'required|unique:companies',
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|email',
        'plafond' => 'integer',
      ]);

      DB::beginTransaction();
      $c=Company::create([
        'area_id' => $request->area_id,
        'city_id' => $request->city_id,
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'email' => $request->email,
        'website' => $request->website,
        'phone' => $request->phone,
        'rek_no_1' => $request->rek_no_1,
        'rek_name_1' => $request->rek_name_1,
        'rek_bank_1' => $request->rek_bank_1,
        'rek_no_2' => $request->rek_no_2,
        'rek_name_2' => $request->rek_name_2,
        'rek_bank_2' => $request->rek_bank_2,
        'plafond' => $request->plafond,
        'cash_account_id' => $request->cash_account_id,
        'bank_account_id' => $request->bank_account_id,
        'mutation_account_id' => $request->mutation_account_id,
        'is_pusat' => ($request->is_pusat?:0),
      ]);
      $tp = DB::table('type_transactions')->selectRaw('id,slug')->get();
      $numbering=array();
      foreach ($tp as $key => $value) {
        array_push($numbering,[
          'company_id' => $c->id,
          'type_transaction_id' => $value->id,
          'urut' => 1,
          'prefix' => '',
          'type' => 'counter',
          'format_data' => null,
          'last_value' => 1
        ]);
        array_push($numbering,[
          'company_id' => $c->id,
          'type_transaction_id' => $value->id,
          'urut' => 2,
          'prefix' => strtoupper($value->slug),
          'type' => 'roman',
          'format_data' => 'm',
          'last_value' => 0
        ]);
        array_push($numbering,[
          'company_id' => $c->id,
          'type_transaction_id' => $value->id,
          'urut' => 3,
          'prefix' => '',
          'type' => 'date',
          'format_data' => 'Y',
          'last_value' => 0
        ]);
      }
      DB::table('company_numberings')->insert($numbering);
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
      $c = Company::find($id);
      return Response::json($c,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['area'] = Area::all();
      $data['city'] = City::all();
      $data['item'] = Company::find($id);
      $data['account'] = Account::with('parent')->where('is_base', 0)->orderBy('code')->get();
      // dd($data);
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
        'code' => 'required|unique:companies,code,'.$id,
        'name' => 'required',
        'city_id' => 'required',
        'address' => 'required',
        'email' => 'required|email'
      ]);

      DB::beginTransaction();
      Company::find($id)->update([
        'area_id' => $request->area_id,
        'city_id' => $request->city_id,
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'email' => $request->email,
        'website' => $request->website,
        'phone' => $request->phone,
        'rek_no_1' => $request->rek_no_1,
        'rek_name_1' => $request->rek_name_1,
        'rek_bank_1' => $request->rek_bank_1,
        'rek_no_2' => $request->rek_no_2,
        'rek_name_2' => $request->rek_name_2,
        'rek_bank_2' => $request->rek_bank_2,
        'plafond' => $request->plafond ?? 0,
        'cash_account_id' => $request->cash_account_id,
        'bank_account_id' => $request->bank_account_id,
        'mutation_account_id' => $request->mutation_account_id,
        'is_pusat' => $request->is_pusat,
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
      Company::find($id)->delete();
      DB::commit();
      return Response::json(null);

    }

    public function numbering_index()
    {
      $tp = TypeTransaction::all();
      return Response::json($tp,200,[],JSON_NUMERIC_CHECK);

    }

    public function format_store(Request $request)
    {
      // dd($request);
      $request->validate([
        // 'prefix' => 'required',
        'urut' => 'required',
        'type' => 'required',
        'format_date' => 'required_if:type,date',
        'format_roman' => 'required_if:type,roman',
        'last_value' => 'required_if:type,counter',
      ]);
      $wr="";
      if (isset($request->ids)) {
        $wr.=" AND id != $request->ids";
      }
      $cn=CompanyNumbering::whereRaw("company_id = $request->company_id and type_transaction_id = $request->type_transaction_id $wr")->select('prefix')->get();
      $pref=[];
      foreach ($cn as $key => $value) {
        $pref[]=$value->prefix;
      }
      //validasi sama prefix
      // if (in_array($request->prefix,$pref)) {
      //   return Response::json(['message' => 'Prefix Sudah digunakan!'],500);
      // }
      DB::beginTransaction();
      if ($request->type=="roman") {
        $fd=$request->format_roman;
      } elseif ($request->type=="date") {
        $fd=$request->format_date;
      } else {
        $fd=null;
      }
      if (isset($request->ids)) {
        CompanyNumbering::find($request->ids)->update([
          'company_id' => $request->company_id,
          'type_transaction_id' => $request->type_transaction_id,
          'urut' => $request->urut,
          'prefix' => ($request->prefix?:""),
          'type' => $request->type,
          'format_data' => $fd,
          'last_value' => ($request->type=="counter"?$request->last_value:0)
        ]);
      } else {
        CompanyNumbering::create([
          'company_id' => $request->company_id,
          'type_transaction_id' => $request->type_transaction_id,
          'urut' => $request->urut,
          'prefix' => ($request->prefix?:""),
          'type' => $request->type,
          'format_data' => $fd,
          'last_value' => ($request->type=="counter"?$request->last_value:0)
        ]);
      }
      DB::commit();

      return Response::json(null);
    }
    public function company_numbering($cid,$fid)
    {
      $data['item']=TypeTransaction::find($fid);
      $data['detail']=CompanyNumbering::where('company_id', $cid)->where('type_transaction_id', $fid)->orderBy('urut','asc')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function edit_format($id)
    {
      $c = CompanyNumbering::find($id);
      return Response::json($c,200,[],JSON_NUMERIC_CHECK);
    }
    public function delete_format($id)
    {
      $c = CompanyNumbering::find($id)->delete();
      return Response::json(null);
    }

    public function warehouse($id)
    {
      $data['gudang'] = Warehouse::with('type')->where('company_id', $id)->get();
      $data['city'] = City::all();
      $data['type'] = WarehouseType::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_gudang(Request $request)
    {
      // dd($request);
      if (isset($request->id)) {
        $request->validate([
          'code' => 'required|unique:warehouses,code,'.$request->id,
          'warehouse_type_id' =>'required'
        ]);
      } else {
        $request->validate([
          'code' => 'required|unique:warehouses,code',
          'warehouse_type_id' =>'required'
        ]);
      }
      DB::beginTransaction();
      $g=Warehouse::find($request->id);
      if (empty($request->id)) {
        $g = new Warehouse;
      }
      $g->company_id=$request->company_id;
      $g->city_id=$request->city_id;
      $g->warehouse_type_id=$request->warehouse_type_id;
      $g->code=$request->code;
      $g->name=$request->name;
      $g->address=$request->address;
      $g->capacity_volume=$request->capacity_volume;
      $g->capacity_tonase=$request->capacity_tonase;
      $g->save();

      if(!isset($request->id)) {
          Rack::create([
        'warehouse_id' => $g->id,
        'code' => 'Handling Area',
        'barcode' => '',
        'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
        'capacity_volume' => 100,
        'capacity_tonase' => 100,
      ]);

      Rack::create([
        'warehouse_id' => $g->id,
        'code' => 'Picking Area',
        'barcode' => '',
        'storage_type_id' => StorageType::where('is_picking_area', 1)->first()->id,
        'capacity_volume' => 100,
        'capacity_tonase' => 100,
        ]);
      }

      DB::commit();
      return Response::json(null);
    }

    public function warehouse_detail($id)
    {
      $c = Warehouse::find($id);
      // return $c->toJson();
      return Response::json($c,200,[],JSON_NUMERIC_CHECK);
    }

    public function warehouse_delete($id)
    {
      DB::beginTransaction();
      $c = Warehouse::find($id)->update([
        'is_active' => 0
      ]);
      DB::commit();
      return Response::json(null);
    }
}

<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\CostType;
use App\Model\CashCategory;
use App\Model\Contact;
use App\Model\Company;
use App\Abstracts\Setting\Operational\CostType AS CT;
use Response;
use DB;
use Exception;

class CostTypeController extends Controller
{

    /*
      Date : 05-03-2020
      Description : Menampilkan daftar jenis biaya. Response
                    berisi id jenis biaya, kode, & nama
      Developer : Didin
      Status : Edit
    */
    public function index(Request $request)
    {
        $cost_type = DB::table('cost_types')
        ->whereNotNull('parent_id')
        ->select("id", 'code', 'name');
        if($request->filled('is_auto_invoice')) {
            $cost_type->where('cost_types.is_auto_invoice', $request->is_auto_invoice);
        }
        if($request->filled('is_invoice')) {
            $cost_type->where('cost_types.is_invoice', $request->is_invoice);
        }
        if($request->filled('is_operasional')) {
            $cost_type = $cost_type->where(function($query) use ($request) {
                $query->where('cost_types.is_operasional', $request->is_operasional);
                $query->orWhere('cost_types.is_shipment', 1);
            });
        }
        if($request->filled('company_id')) {
            $cost_type->where('cost_types.company_id', $request->company_id);
        }
        $cost_type = $cost_type->get();

        return Response::json($cost_type, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['account']=DB::table('accounts as a')->leftJoin('account_types as ats','ats.id','a.type_id')->selectRaw('a.id,a.code,a.name,ats.is_payable,a.no_cash_bank')->where('a.is_base', 0)->get();
      $data['parent']=CostType::where('parent_id',null)->get();
      $data['cash_category']=CashCategory::where('is_base',0)->get();
      $c=Account::whereHas('type', function($query){
        $query->where('id',1);
      })->get();
      // dd($c);
      $data['cash_acc_id']=[];
      foreach ($c as $key => $value) {
        $data['cash_acc_id'][]=$value->id;
      }

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
      try {
        if (isset($request->category)) {
        $request->validate([
          'company_id' => 'required',
          'akun_biaya' => 'required',
          'is_overtime' => 'required',
          'akun_uang_muka' => 'required_if:type,1',
          'akun_kas_hutang' => 'required',
          'vendor_id' => 'required',
          'name' => 'required',
          'initial_cost' => 'required|integer',
          'code' => 'required|unique:cost_types,code',
          'ppn_cost' => 'required_if:is_ppn,1'
        ]);
        $cost_type = CostType::create([
            'company_id' => $request->company_id,
            'parent_id' => $request->category,
            'code' => $request->code,
            'name' => $request->name,
            'vendor_id' => $request->vendor_id,
            'cost_route_type_id' => $request->cost_route_type_id,
            'type' => $request->type,
            'akun_biaya' => $request->akun_biaya,
            'akun_uang_muka' => $request->akun_uang_muka,
            'akun_kas_hutang' => $request->akun_kas_hutang,
            'cash_category_id' => $request->cash_category_id,
            'initial_cost' => $request->initial_cost,
            'is_bbm' => $request->is_bbm,
            'is_operasional' => $request->is_operasional,
            'is_invoice' => $request->is_invoice,
            'is_biaya_lain' => $request->is_biaya_lain,
            'is_ppn' => $request->is_ppn,
            'ppn_cost' => $request->ppn_cost,
            'percentage' => $request->percentage ?? 0,
            'is_insurance' => $request->is_insurance ?? 0,
            'is_auto_invoice' => $request->is_auto_invoice ?? 0,
            'is_shipment' => $request->is_shipment ?? 0,
            'is_overtime' => $request->is_overtime,
            'qty' => ($request->is_bbm==1 || $request->is_shipment==1?($request->qty ?? 1):1),
            'cost' => ($request->is_bbm==1?$request->cost:$request->initial_cost),
        ]);

        CT::generateVendorPrice($cost_type->id);
      } else {
        $request->validate([
          'name' => 'required',
          'code' => 'required|unique:cost_types,code',
        ]);

        CostType::create([
          'code' => $request->code,
          'name' => $request->name,
        ]);
      }
      } catch (Exception $e) {
        DB::rollback();
        $msg = $e->getMessage();
        return response()->json(['message' => $msg], 421);
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
      $data['item'] = CT::show($id);
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
      $data['account']=DB::table('accounts as a')->leftJoin('account_types as ats','ats.id','a.type_id')->selectRaw('a.id,a.code,a.name,ats.is_payable,a.no_cash_bank')->where('a.is_base', 0)->get();
      $data['parent']=CostType::where('parent_id',null)->get();
      $data['cash_category']=CashCategory::where('is_base',0)->get();
      $data['item']=CT::show($id);
      $c=Account::whereHas('type', function($query){
        $query->where('id',1);
      })->get();
      // dd($c);
      $data['cash_acc_id']=[];
      foreach ($c as $key => $value) {
        $data['cash_acc_id'][]=$value->id;
      }

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
      // dd($request);
      DB::beginTransaction();
      if (isset($request->category)) {
        $request->validate([
          'company_id' => 'required',
          'akun_biaya' => 'required',
          'is_overtime' => 'required',
          'akun_uang_muka' => 'required_if:type,1',
          'akun_kas_hutang' => 'required',
          'vendor_id' => 'required',
          'name' => 'required',
          'initial_cost' => 'required|integer',
          'ppn_cost' => 'required_if:is_ppn,1',
          'code' => 'required|unique:cost_types,code,'.$id
        ]);

        CostType::find($id)->update([
          'company_id' => $request->company_id,
          'parent_id' => $request->category,
          'code' => $request->code,
          'name' => $request->name,
          'vendor_id' => $request->vendor_id,
          'cost_route_type_id' => $request->cost_route_type_id,
          'type' => $request->type,
          'akun_biaya' => $request->akun_biaya,
          'akun_uang_muka' => $request->akun_uang_muka,
          'akun_kas_hutang' => $request->akun_kas_hutang,
          'cash_category_id' => $request->cash_category_id,
          'initial_cost' => $request->initial_cost,
          'is_bbm' => $request->is_bbm,
          'is_operasional' => $request->is_operasional,
          'is_invoice' => $request->is_invoice,
          'is_biaya_lain' => $request->is_biaya_lain,
          'is_auto_invoice' => $request->is_auto_invoice ?? 0,
          'is_shipment' => $request->is_shipment ?? 0,
          'is_ppn' => $request->is_ppn,
          'ppn_cost' => $request->ppn_cost,
          'percentage' => $request->percentage ?? 0,
          'is_insurance' => $request->is_insurance ?? 0,
          'is_overtime' => $request->is_overtime,
          'qty' => ($request->is_bbm==1 || $request->is_shipment==1?($request->qty ?? 1):1),
          'cost' => ($request->is_bbm==1?$request->cost:$request->initial_cost),
        ]);
        CT::generateVendorPrice($id);
      } else {
        $request->validate([
          'name' => 'required',
          'code' => 'required|unique:cost_types,code,'.$id
        ]);
        CostType::find($id)->update([
          'code' => $request->code,
          'name' => $request->name,
        ]);
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
      DB::beginTransaction();
      CostType::find($id)->delete();
      DB::commit();
    }
}

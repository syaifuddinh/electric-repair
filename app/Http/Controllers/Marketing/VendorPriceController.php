<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\VendorType;
use App\Model\Bank;
use App\Model\Account;
use App\Model\Company;
use App\Model\City;
use App\Model\AddressType;
use App\Model\ContactAddress;
use App\Model\ContactFile;
use App\Model\VendorPrice;
use App\Model\Route as Trayek;
use App\Model\VehicleType;
use App\Model\Moda;
use App\Model\Commodity;
use App\Model\Piece;
use App\Model\Service;
use App\Model\PriceList;
use App\Model\ServiceType;
use App\Model\ContainerType;
use App\Model\Rack;
use Carbon\Carbon;
use Response;
use DB;

class VendorPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['commodity']=Commodity::all();
      $data['service']=Service::with('service_type')->get();
      $data['piece']=Piece::all();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['container_type']=ContainerType::all();
      $data['rack']=Rack::all();
      $data['item'] = ['company_id' => auth()->user()->company_id];
      
      // $data['service_type']=ServiceType::orderBy('id')->get();
      $data['type_1']=[];
      $data['type_2']=[];
      $data['type_3']=[];
      $data['type_4']=[];
      $data['type_5']=[];
      $data['type_6']=[];
      $data['type_7']=[];
      foreach ($data['service'] as $value) {
        if ($value->service_type->id==1) {
          $data['type_1'][]=$value->id;
        } elseif ($value->service_type->id==2) {
          $data['type_2'][]=$value->id;
        } elseif ($value->service_type->id==3) {
          $data['type_3'][]=$value->id;
        } elseif ($value->service_type->id==4) {
          $data['type_4'][]=$value->id;
        } elseif ($value->service_type->id==5) {
          $data['type_5'][]=$value->id;
        } elseif ($value->service_type->id==6) {
          $data['type_6'][]=$value->id;
        } else {
          $data['type_7'][]=$value->id;
        }
      }

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 05-03-2020
      Description : Menyimpan tarif vendor
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
      $request->validate([
        // 'stype_id' => 'required',
        'company_id' => 'required',
        'cost_category' => 'required',
        'vendor_id' => 'required',
        'cost_type_id' => 'required',
        'date' => 'required',
        'vehicle_type_id' => 'required_if:cost_category,2',
        'route_id' => 'required_if:cost_category,2|required_if:cost_category,3',
        "price_full" => 'required',
        'container_type_id' => 'required_if:cost_category,3',
      ], [
        'company_id.required' => 'Cabang tidak boleh kosong',
        'cost_category.required' => 'Kelompok biaya tidak boleh kosong',
        'vendor_id.required' => 'Vendor tidak boleh kosong',
        'cost_type_id.required' => 'Jenis biaya tidak boleh kosong',
        'container_type_id.required_if' => 'Kontainer tidak boleh kosong',
        'vehicle_type_id.required_if' => 'Kendaraan tidak boleh kosong',
        'route_id.required_if' => 'Trayek tidak boleh kosong',
        'price_full.required' => 'Biaya satuan tidak boleh kosong',
        'date.required' => 'Tanggal mulai berlaku tidak boleh kosong'
      ]);

      DB::beginTransaction();
      $v = VendorPrice::create([
        "vendor_id" => $request->vendor_id,
        "company_id" => $request->company_id,
        "route_id" => $request->route_id,
        "vehicle_type_id" => $request->vehicle_type_id,
        "price_full" => $request->price_full,
        "created_by" => auth()->id(),

        'container_type_id' => $request->container_type_id,
        'cost_type_id' => $request->cost_type_id,
        'cost_category' => $request->cost_category,
        'date' => Carbon::parse($request->date)->format('Y-m-d')
      ]);

      $this->filterDate($v->id);
      DB::commit();

      return Response::json(['message' => 'Data successfully saved']);
    }

    /*
      Date : 05-03-2020
      Description : Memfilter tarif vendor yang dengan tanggal 
                    terbaru
      Developer : Didin
      Status : Edit
    */
    public function filterDate($id){
        $vendor_price = DB::table('vendor_prices')
        ->whereId($id)
        ->first();

        $similar = DB::table('vendor_prices')
        ->whereCompanyId($vendor_price->company_id)
        ->whereCostTypeId($vendor_price->cost_type_id)
        ->whereVendorId($vendor_price->vendor_id)
        ->get();

        $current_date = Carbon::parse($vendor_price->date);
        foreach($similar as $unit) {
            $date = Carbon::parse($unit->date);
            if($date->gt($current_date)) {
                $current_date = $date;
            }
        }

        DB::table('vendor_prices')
        ->whereCompanyId($vendor_price->company_id)
        ->whereCostTypeId($vendor_price->cost_type_id)
        ->whereVendorId($vendor_price->vendor_id)
        ->where('date', $date->format('Y-m-d') )
        ->update([
            'is_used' => 1
        ]);

        DB::table('vendor_prices')
        ->whereCompanyId($vendor_price->company_id)
        ->whereCostTypeId($vendor_price->cost_type_id)
        ->whereVendorId($vendor_price->vendor_id)
        ->where('date', '!=', $date->format('Y-m-d') )
        ->update([
            'is_used' => 0
        ]);
    } 

    /*
      Date : 05-03-2020
      Description : Menampilkan detail tarif vendor 
                    berdasarkan ID
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
      $c=VendorPrice::with('company:id,name', 'vendor:id,name','vehicle_type:id,name','route','container_type:id,code,name,size,unit')
      ->leftJoin('cost_types', 'cost_types.id', 'vendor_prices.cost_type_id')
      ->where('vendor_prices.id', $id)
      ->select('vendor_prices.company_id', 'vendor_prices.route_id', 'vendor_prices.vehicle_type_id', 'vendor_prices.container_type_id', 'vendor_prices.vendor_id', 'vendor_prices.date', 'vendor_prices.price_full', 'cost_types.name AS cost_type_name', 'cost_category')
      ->first();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 05-03-2020
      Description : Menampilkan detail tarif vendor, daftar   
                    perusahaan, daftar jenis kendaraan, dan daftar trayek
      Developer : Didin
      Status : Edit
    */
    public function edit($id)
    {
      $data['company']=companyAdmin(auth()->id());
      $data['vehicle_type']=VehicleType::select('id', 'name')->get();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")
      ->select('id', 'name')
      ->get();
      $data['item']=VendorPrice::find($id);
      $data['item']->date = Carbon::parse($data['item']->date)->format('d-m-Y');
     

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 05-03-2020
      Description : Meng-update tarif vendor
      Developer : Didin
      Status : Edit
    */
    public function update(Request $request, $id)
    {
        $request->validate([
        // 'stype_id' => 'required',
        'company_id' => 'required',
        'cost_category' => 'required',
        'vendor_id' => 'required',
        'cost_type_id' => 'required',
        'date' => 'required',
        'vehicle_type_id' => 'required_if:cost_category,2',
        'route_id' => 'required_if:cost_category,2|required_if:cost_category,3',
        "price_full" => 'required',
        'container_type_id' => 'required_if:cost_category,3',
      ], [
        'company_id.required' => 'Cabang tidak boleh kosong',
        'cost_category.required' => 'Kelompok biaya tidak boleh kosong',
        'vendor_id.required' => 'Vendor tidak boleh kosong',
        'cost_type_id.required' => 'Jenis biaya tidak boleh kosong',
        'container_type_id.required_if' => 'Kontainer tidak boleh kosong',
        'vehicle_type_id.required_if' => 'Kendaraan tidak boleh kosong',
        'route_id.required_if' => 'Trayek tidak boleh kosong',
        'price_full.required' => 'Biaya satuan tidak boleh kosong',
        'date.required' => 'Tanggal mulai berlaku tidak boleh kosong'
      ]);

        DB::beginTransaction();
        $date = Carbon::parse($request->date)->format('Y-m-d');
        $vendor_price = VendorPrice::whereId($id);
        if($vendor_price->first()->date != $date) {
            DB::table('vendor_prices')->insert([
                "vendor_id" => $request->vendor_id,
                "company_id" => $request->company_id,
                "route_id" => $request->route_id,
                "vehicle_type_id" => $request->vehicle_type_id,
                "price_full" => $request->price_full,
                "created_by" => auth()->id(),

                'container_type_id' => $request->container_type_id,
                'cost_type_id' => $request->cost_type_id,
                'cost_category' => $request->cost_category,
                'date' => Carbon::parse($request->date)->format('Y-m-d')
            ]);
        } else {
            $vendor_price->update([
                "vendor_id" => $request->vendor_id,
                "company_id" => $request->company_id,
                "route_id" => $request->route_id,
                "vehicle_type_id" => $request->vehicle_type_id,
                "price_full" => $request->price_full,
                'container_type_id' => $request->container_type_id,
                'cost_type_id' => $request->cost_type_id,
                'cost_category' => $request->cost_category,
                'date' => Carbon::parse($request->date)->format('Y-m-d')
            ]);

        }
        $this->filterDate($id);
        DB::commit();

        return Response::json(['message' => 'Data berhasil diupdate']);
    }

    /*
      Date : 05-03-2020
      Description : Mencari detail tarif vendor berdasarkan tipe kontainer, id vendor, tipe kendaraan, dan jenis biaya
      Developer : Didin
      Status : Edit
    */
    public function search(Request $request, $flag)
    {
        $vendor_price = DB::table('vendor_prices')
        ->whereCostTypeId($request->cost_type_id)
        ->whereVendorId($request->vendor_id);
        if($flag == 'trucking') {
            $vendor_price->whereCostCategory(2)
            ->whereVehicleTypeId($request->vehicle_type_id);
        } else if($flag == 'container') {
            $vendor_price->whereCostCategory(3)
            ->whereContainerTypeId($request->container_type_id);
        } else if($flag == 'job_order'){
            $vendor_price->whereCostCategory(1);            
        }

        $vendor_price->select('price_full');

        return Response::json($vendor_price->first()->price_full ?? 0);
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
          VendorPrice::find($id)->delete();
        DB::commit();

        return Response::json(null);
    }
}

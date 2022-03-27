<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\DeliveryOrderDriver;
use App\Model\JobStatusHistory;
use App\Model\DeliveryRejectHistory;
use App\Model\Manifest;
use App\Model\DriverMessage;
use Carbon\Carbon;
use Response;
use DB;
use Auth;

class VendorMobileController extends Controller
{
    public function get_user(Request $request)
    {
      $user=DB::table('contacts')
      ->leftJoin('vehicles','vehicles.id','contacts.vehicle_id')
      ->leftJoin('delivery_order_drivers as dod','dod.driver_id','contacts.id')
      ->where('contacts.id',$request->user()->id)
      ->selectRaw('
        contacts.id,
        contacts.name,
        contacts.address,
        contacts.api_token,
        contacts.is_driver,
        contacts.vehicle_id,
        contacts.lat as latitude,
        contacts.lng as longitude,
        vehicles.nopol,
        sum(if(dod.is_finish=1,1,0)) as job_finish,
        sum(if(dod.is_finish=0,1,0)) as job_proses
      ')->groupBy('contacts.id')
      ->first();
      $data=[
        'status' => 'OK',
        'message' => 'Your User Data',
        'data' => $user
      ];
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }
    public function send_message(Request $request)
    {
      $request->validate([
        'message' => 'required',
        'driver_id' => 'required',
      ]);

      DB::beginTransaction();
      $input=$request->only(['driver_id', 'message']);
      DriverMessage::create($input);
      DB::commit();

      return response()->json([
        'status' => 'OK',
        'message' => 'Pesan Terkirim'
      ]);
    }
    public function login(Request $request)
    {
      $request->validate([
        'email' => 'required',
        'password' => 'required',
      ],[
        'email.required' => 'Email harus diisi!',
        'password.required' => 'Password harus diisi!',
      ]);

      $auth=Auth::guard('contact')->attempt([
        'email' => $request->email,
        'password' => $request->password,
      ]);
      if ($auth) {
        $item=DB::table('contacts')
        ->where('contacts.email',$request->email)
        ->leftJoin('vehicles','vehicles.id','contacts.vehicle_id')
        ->selectRaw('contacts.id,contacts.name,contacts.address,contacts.api_token,contacts.is_driver,contacts.vehicle_id,vehicles.nopol')
        ->first();

        Contact::find($item->id)->update([
          'last_login' => Carbon::now(),
          'is_login' => 1
        ]);
        $dt=[];
        foreach ($item as $key => $value) {
          $dt[$key]=$value;
        }
        if ($item->is_driver) {
          /*
          $job=DB::table('delivery_order_drivers')
          ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
          ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
          ->leftJoin('container_types','container_types.id','manifests.container_type_id')
          ->leftJoin('routes','routes.id','manifests.route_id')
          ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
          ->where('delivery_order_drivers.driver_id',$item->id)
          ->selectRaw('
          delivery_order_drivers.id,
          delivery_order_drivers.code as surat_jalan,
          delivery_order_drivers.is_finish as is_finish,
          manifests.code as no_manifest,
          routes.name as rute,
          job_statuses.name as status,
          if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as tipe_kendaraan,
          job_statuses.is_finish
          ')
          ->orderBy('delivery_order_drivers.is_finish','asc')
          ->orderBy('delivery_order_drivers.updated_at','desc')
          ->get();
          $dt['job_proses']=[];
          $dt['job_finish']=[];
          foreach ($job as $vl) {
            if (!$vl->is_finish) {
              $dt['job_proses'][]=$vl;
            } else {
              $dt['job_finish'][]=$vl;
            }
          }
          */
          return Response::json(['status' => 'OK','message' => 'Login Berhasil','data' => $dt],200,[],JSON_NUMERIC_CHECK);
        } else {
          return Response::json(['status' => 'ERROR','message' => 'Login Gagal! Bukan Akun Driver.','data' => null],500,[],JSON_NUMERIC_CHECK);
        }
      } else {
        return Response::json(['status' => 'ERROR','message' => 'Login Gagal! Akun tidak ditemukan / password salah','data' => null],500,[],JSON_NUMERIC_CHECK);
      }
    }

    public function get_list_vehicle(Request $request)
    {
      $request->validate([
        'driver_id' => 'required'
      ],[
        'driver_id.required' => 'Id Driver harus dimasukkan'
      ]);
      $vehicle=array();
      DB::table('vehicle_contacts')
      ->leftJoin('vehicles','vehicles.id','vehicle_contacts.vehicle_id')
      ->where('contact_id', $request->driver_id)
      ->orderBy('vehicles.id')
      ->selectRaw('vehicles.id,vehicles.nopol')
      ->chunk(50, function($chunk) use (&$vehicle) {
        foreach ($chunk as $key => $value) {
          array_push($vehicle, $value);
        }
      });
      $data=[
        'status' => 'OK',
        'message' => 'List Kendaraan',
        'data' => $vehicle
      ];
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function post_vehicle(Request $request)
    {
      $request->validate([
        'driver_id' => 'required',
        'vehicle_id' => 'required'
      ],[
        'driver_id.required' => 'Id Driver harus dimasukkan',
        'vehicle_id.required' => 'Id Kendaraan harus dimasukkan',
      ]);
      DB::beginTransaction();
      Contact::find($request->driver_id)->update([
        'vehicle_id' => $request->vehicle_id
      ]);
      DB::commit();

      return Response::json(['status' => 'OK','message' => 'Kendaraan telah dipilih','data' => null],200,[],JSON_NUMERIC_CHECK);
    }

    public function get_list_job(Request $request)
    {
      $request->validate([
        'driver_id' => 'required',
        'is_finish' => 'nullable|numeric',
        'from_date' => 'nullable|date',
        'to_date' => 'nullable|date',
        'job_status_id' => 'nullable|numeric',
      ],[
        'driver_id.required' => 'Id Driver harus dimasukkan',
      ]);
      $wr="delivery_order_drivers.driver_id = {$request->driver_id}";
      if ($request->job_status_id) {
        $wr.=" AND delivery_order_drivers.job_status_id = {$request->job_status_id}";
      } else if ($request->is_finish) {
        $wr.=" AND delivery_order_drivers.is_finish = {$request->is_finish}";
      }
      /* FILTER TANGGAL */
      if ($request->from_date&&$request->to_date) {
        $dateStart=Carbon::parse($request->from_date);
        $dateFinish=Carbon::parse($request->to_date);
        $wr.=" AND ( delivery_order_drivers.pick_date BETWEEN '{$dateStart}' AND '{$dateFinish}' )";
      } else if ($request->from_date) {
        $dateStart=Carbon::parse($request->from_date);
        $wr.=" AND delivery_order_drivers.pick_date >= '{$dateStart}'";
      } else if ($request->to_date) {
        $dateFinish=Carbon::parse($request->to_date);
        $wr.=" AND delivery_order_drivers.pick_date <= '{$dateFinish}'";
      }
      /* --------------------------------- */

      $item=DB::table('delivery_order_drivers')
      ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
      ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
      ->leftJoin('container_types','container_types.id','manifests.container_type_id')
      ->leftJoin('routes','routes.id','manifests.route_id')
      ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
      ->whereRaw($wr)
      ->selectRaw('
      delivery_order_drivers.id,
      delivery_order_drivers.code as surat_jalan,
      delivery_order_drivers.is_finish as is_finish,
      delivery_order_drivers.pick_date as pickup_time,
      manifests.code as no_manifest,
      routes.name as rute,
      job_statuses.name as status,
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as tipe_kendaraan
      ')
      ->orderBy('delivery_order_drivers.is_finish','asc')
      ->orderBy('delivery_order_drivers.updated_at','desc')
      ->get();
      return Response::json($item,200,[],JSON_NUMERIC_CHECK);
    }

    public function detail_job(Request $request)
    {
      $request->validate([
        'job_id' => 'required',
      ],[
        'job_id.required' => 'Id Job harus dimasukkan',
      ]);
      $item=DB::table('delivery_order_drivers')
      ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
      ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
      ->leftJoin('container_types','container_types.id','manifests.container_type_id')
      ->leftJoin('routes','routes.id','manifests.route_id')
      ->leftJoin('containers','containers.id','manifests.container_id')
      ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
      ->leftJoin('contacts','contacts.id','delivery_order_drivers.driver_id')
      ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
      ->where('delivery_order_drivers.id',$request->job_id)
      ->selectRaw('
      delivery_order_drivers.id,
      delivery_order_drivers.code as surat_jalan,
      delivery_order_drivers.job_status_id,
      manifests.code as no_manifest,
      routes.name as rute,
      manifests.id as manifest_id,
      contacts.name as driver,
      vehicles.nopol as nopol,
      containers.container_no as no_container,
      delivery_order_drivers.is_finish,
      if(manifests.is_full=1 and is_container = 1,\'FCL\',if(manifests.is_full=0 and is_container = 1,\'LCL\',if(manifests.is_full=1 and is_container = 0,\'FTL\',\'LTL\'))) as tipe_angkut,
      if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as tipe_kendaraan,
      job_statuses.name as status,
      (select if(is_cancel=0 and is_reject=0,name,null) from job_statuses as jss where urut > job_statuses.urut order by urut asc limit 1) as next_status
      ')->first();
      $history=DB::table('job_status_histories')
      ->leftJoin('delivery_order_drivers','delivery_order_drivers.id','job_status_histories.delivery_id')
      ->leftJoin('job_statuses','job_statuses.id','job_status_histories.job_status_id')
      ->where('job_status_histories.delivery_id', $item->id)
      ->selectRaw('job_statuses.name,job_status_histories.created_at')
      ->orderBy('job_status_histories.created_at','asc')->get();
      $data=json_decode(json_encode($item),true);
      foreach ($history as $key => $value) {
        $data['status_history'][]=[
          'status' => $value->name,
          'update' => $value->created_at,
        ];
      }
      $barang=DB::table('manifest_details')
      ->leftJoin('job_order_details','job_order_details.id','manifest_details.job_order_detail_id')
      ->leftJoin('job_orders','job_orders.id','job_order_details.header_id')
      ->leftJoin('contacts as to','to.id','job_orders.receiver_id')
      ->leftJoin('commodities','commodities.id','job_order_details.commodity_id')
      ->leftJoin('quotation_details','quotation_details.id','job_order_details.quotation_detail_id')
      ->leftJoin('pieces','pieces.id','job_order_details.piece_id')
      ->where('manifest_details.header_id', $item->manifest_id)
      ->selectRaw('
        job_order_details.item_name,
        commodities.name as komoditas,
        manifest_details.transported as qty,
        pieces.name as satuan,
        job_order_details.weight,
        job_order_details.long,
        job_order_details.high,
        to.name as nama_tujuan,
        to.address as alamat_tujuan
      ')
      ->get();
      foreach ($barang as $key => $value) {
        $data['barang'][]=(array)$value;
      }
      $tot=[
        'status' => 'OK',
        'message' => 'Detail Job.',
        'data' => $data
      ];
      // dd($barang);
      return Response::json($tot,200,[],JSON_NUMERIC_CHECK);
    }

    public function update_status_job(Request $request)
    {
      $request->validate([
        'job_id' => 'required'
      ],[
        'job_id.required' => 'Id Job harus dimasukkan'
      ]);

      DB::beginTransaction();
      $i=DeliveryOrderDriver::findOrFail($request->job_id);
      if ($i->driver_id!=auth()->id()) {
        return response()->json([
          'status' => 'ERROR',
          'message' => 'Forbidden Access. Job Order not belong to this driver'
        ],500);
      }
      $mn=DB::table('manifests')->where('id', $i->manifest_id)->first();
      if ($i->is_finish) {
        return Response::json(['status' => 'ERROR','message' => 'Job ini sudah finish!','data' => null],500,[],JSON_NUMERIC_CHECK);
      }
      $next=DB::select("select job_status_id, (select concat(id,'|',name,'|',is_finish) from job_statuses where urut > js.urut order by urut asc limit 1) as nextstatus from delivery_order_drivers as dod
      left join job_statuses as js on dod.job_status_id = js.id
      where dod.id = $request->job_id
      ")[0];
      $xp=explode("|",$next->nextstatus);
      $update=[
        'updated_at' => Carbon::now()
      ];
      if ($xp[0]!="") {
        $update['job_status_id']=$xp[0];
        if ($xp[2]=="1") {
          $update['is_finish']=1;
        }
        JobStatusHistory::create([
          'delivery_id' => $request->job_id,
          'job_status_id' => $xp[0],
          'driver_id' => $i->driver_id,
          'vehicle_id' => $i->vehicle_id,
          'vendor_id' => $i->vendor_id,
        ]);
      }
      $i->update($update);
      // dd($status);
      DB::commit();

      return Response::json(['status' => 'OK','message' => 'Status berhasil diupdate!','data' => null],200,[],JSON_NUMERIC_CHECK);
    }

    public function reject_job(Request $request)
    {
      $request->validate([
        'job_id' => 'required',
        'description' => 'required'
      ],[
        'job_id.required' => 'Id Job harus dimasukkan',
        'description.required' => 'Alasan Penolakan harus diisi'
      ]);
      DB::beginTransaction();
      $dod=DeliveryOrderDriver::find($request->job_id);
      if ($dod->job_status_id>=3) {
        return response()->json([
          'status' => 'ERROR',
          'message' => 'Job Order sudah diterima / dalam pengangkutan, tidak dapat membatalkan job.'
        ],500);
      }
      DeliveryRejectHistory::create([
        'delivery_order_id' => $request->job_id,
        'driver_id' => auth()->id(),
        'description' => $request->description
      ]);
      $stt=DB::table('job_statuses')->where('is_reject', 1)->first();
      $updates=[
        'vehicle_id' => null,
        'driver_id' => null,
      ];
      Manifest::find($dod->manifest_id)->update($updates);
      $updates['job_status_id'] = 13;
      $dod->update($updates);
      DB::commit();

      return Response::json(['status' => 'OK','message' => 'Job telah ditolak!','data' => null],200,[],JSON_NUMERIC_CHECK);
    }

    public function logout(Request $request)
    {
      DB::beginTransaction();
      $c=Contact::find(auth()->id())->update([
        'vehicle_id' => null,
        'is_login' => 0,
        'api_token' => str_random(100),
        'lat' => null,
        'lng' => null,
      ]);
      DB::commit();

      return Response::json(['status' => 'OK','message' => 'Anda telah Logout!','data' => null],200,[],JSON_NUMERIC_CHECK);
    }

    public function change_password(Request $request)
    {
      $request->validate([
        'old_password' => 'required',
        'new_password' => 'required',
      ]);
      $find=Contact::find(auth()->id());
      $attempt=Auth::guard('contact')->attempt([
        'email' => $find->email,
        'password' => $request->old_password
      ]);

      if ($attempt) {
        DB::beginTransaction();
        $find->update([
          'password' => bcrypt($request->new_password)
        ]);
        DB::commit();

        return Response::json(['status' => 'OK','message' => 'Password Berhasil Diganti!','data' => null],200,[],JSON_NUMERIC_CHECK);
      } else {
        return Response::json(['status' => 'ERROR','message' => 'Password Lama Anda tidak cocok!','data' => null],500,[],JSON_NUMERIC_CHECK);
      }
    }

    public function update_location(Request $request)
    {
      $request->validate([
        'lat' => 'required',
        'lng' => 'required',
      ]);

      DB::beginTransaction();
      Contact::where('id',auth()->id())->update([
        'lat' => $request->lat,
        'lng' => $request->lng,
      ]);
      DB::commit();

      return Response::json(['status' => 'OK','message' => 'Lokasi telah diupdate!','data' => null],200,[],JSON_NUMERIC_CHECK);

    }

}

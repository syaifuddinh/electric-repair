<?php

namespace App\Http\Controllers\Vehicle;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Vehicle;
use App\Model\Rack;
use App\Model\Contact;
use App\Model\VehicleMaintenance;
use App\Model\Item;
use App\Model\VehicleMaintenanceType;
use App\Model\VehicleMaintenanceDetail;
use App\Model\UsingItem;
use App\Model\UsingItemDetail;
use App\Model\PurchaseRequest;
use App\Model\PurchaseRequestDetail;
use App\Model\PurchaseOrder;
use App\Model\PurchaseOrderDetail;
use App\Model\Warehouse;
use App\Model\TypeTransaction;
use App\Model\StockTransaction;
use App\Utils\TransactionCode;
use Response;
use App\Abstracts\Inventory\Item AS IT;
use App\Model\WarehouseReceiptDetail;
use App\Model\WarehouseStockDetail;
use Exception;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
  public function create($vehicle_id)
  {
    $data['vendor']=Contact::whereRaw("is_vendor = 1 or is_supplier and vendor_status_approve = 2")->get();
    $data['items']=Item::with('category')->get();
    $data['vehicle']=Vehicle::find($vehicle_id);
    $data['vehicle_maintenance_type']=VehicleMaintenanceType::all();
    return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
  }

    public function store_pengajuan(Request $request, $vehicle_id)
    {
        // dd($request);
        $request->validate([
            'is_internal' => 'required',
            'name' => 'required',
            'km_rencana' => 'required',
            'cost_rencana' => 'required',
            'date_rencana' => 'required',
            'vendor_id' => 'required_if:is_internal,0'
        ],[
            'vendor_id.required_if' => 'Vendor harus dipilih jika perawatan eksternal'
        ]);

        DB::beginTransaction();
        $vehicle=Vehicle::find($vehicle_id);

        $code = new TransactionCode($vehicle->company_id, 'maintenance');
        $code->setCode();
        $trx_code = $code->getCode();

        $input = [
            'company_id' => $vehicle->company_id,
            'vehicle_id' => $vehicle->id,
            'code' => $trx_code,
            'name' => $request->name,
            'km_rencana' => $request->km_rencana,
            'cost_rencana' => $request->cost_rencana,
            'date_rencana' => dateDB($request->date_rencana),
            'date_pengajuan' => date("Y-m-d"),
            'description' => $request->description,
            'is_internal' => $request->is_internal,
            'status' => 2
        ];

        if (!$request->is_internal) {
            $input['vendor_id'] = $request->vendor_id;
        }

        $i=VehicleMaintenance::create($input);

        if (count($request->detail) < 1) {
            return response()->json(['message' => 'Anda belum memasukkan item barang perawatan!'],500,[],JSON_NUMERIC_CHECK);
        }

        if (isset($request->detail)) {
            if ($request->is_internal==1) {
                $u = UsingItem::create([
                    'company_id' => $vehicle->company_id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_maintenance_id' => $i->id,
                    'code' => $trx_code,
                    'date_request' => date("Y-m-d"),
                    'status' => 1,
                    'description' => 'Perawatan Kendaraan - '.$trx_code.' - Nopol : '.$vehicle->nopol
                ]);
            }

            foreach ($request->detail as $key => $value) {
                if (isset($value)) {
                  if(!($value['vehicle_maintenance_type_id'] ?? null)) {
                      throw new Exception('Jenis perawatan tidak boleh kosong');
                  }

                  if(!($value['tipe_kegiatan'] ?? null)) {
                      throw new Exception('Jenis kegiatan tidak boleh kosong');
                  }

                  if(!($value['item_id'] ?? null)) {
                      throw new Exception('Item tidak boleh kosong');
                  }

                  $value['qty'] = $value['qty'] ?? 0;
                  $value['price'] = $value['price'] ?? 0;

                  $vmd=VehicleMaintenanceDetail::create([
                    'header_id' => $i->id,
                    'vehicle_maintenance_type_id' => $value['vehicle_maintenance_type_id'],
                    'tipe_kegiatan' => $value['tipe_kegiatan'],
                    'item_id' => $value['item_id'] ?? null,
                    'rack_id' => $value['rack_id'] ?? null,
                    'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'] ?? null,
                    'cost_rencana' => $value['price'],
                    'qty_rencana' => $value['qty'],
                    'total_rencana' => $value['qty']*$value['price'],
                  ]);

                  if ($request->is_internal==1) {
                    UsingItemDetail::create([
                      'header_id' => $u->id,
                      'vehicle_maintenance_detail_id' => $vmd->id,
                      'item_id' => $value['item_id'] ?? null,
                      'qty' => $value['qty'],
                      'cost' => $value['price'],
                      'total' => $value['qty']*$value['price'],
                    ]);
                  }
                }
            }
    }
    DB::commit();

    return response()->json(null);
  }

  public function show($vm_id)
  {
    $data['item']=VehicleMaintenance::with('vendor')->where('id', $vm_id)->first();
    $data['detail']=VehicleMaintenanceDetail::with('item','item.category')->where('header_id', $vm_id)->get();
    $data['warehouse']=Warehouse::where('company_id', $data['item']->company_id)->get();
    $data['using_item']=DB::table('using_items')->where('vehicle_maintenance_id', $vm_id)->get();
    return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function edit_rencana($vm_id)
  {
    $data['item']=VehicleMaintenance::where('id', $vm_id)->first();
    $data['vendor']=Contact::whereRaw("is_vendor = 1 or is_supplier and vendor_status_approve = 2")->get();
    $data['detail']=VehicleMaintenanceDetail::with('item','item.category')->where('header_id', $vm_id)
    ->leftJoin("racks", 'racks.id', 'vehicle_maintenance_details.rack_id')
    ->select('vehicle_maintenance_details.*', 'racks.code AS rack_code')
    ->get();
    $data['items']=Item::with('category')->get();
    $data['vehicle_maintenance_type']=VehicleMaintenanceType::all();
    return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  /*
      Date : 05-03-2021
      Description : Mengajukan perawatan kendaraan
      Developer : Didin
      Status : Create
    */
  public function store_rencana(Request $request, $vm_id)
  {
        $request->validate([
            'name' => 'required',
            'km_rencana' => 'required',
            'cost_rencana' => 'required',
            'date_rencana' => 'required',
        ], [
          'name.required' => 'Nama perawatan tidak boleh kosong',
          'km_rencana.required' => 'KM rencana tidak boleh kosong',
          'cost_rencana.required' => 'Biaya jasa tidak boleh kosong',
          'date_rencana.required' => 'Tanggal tidak boleh kosong',
        ]);

        if (count($request->detail) < 1) {
          return response()->json(['message' => 'Anda belum memasukkan item barang perawatan!'],500,[],JSON_NUMERIC_CHECK);
        }

        DB::beginTransaction();
        $i=VehicleMaintenance::find($vm_id)->update([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'km_rencana' => $request->km_rencana,
            'cost_rencana' => $request->cost_rencana,
            'date_rencana' => dateDB($request->date_rencana),
            'description' => $request->description,
            'status' => 3
        ]);
        $ss=VehicleMaintenance::find($vm_id);
        $v=Vehicle::find($ss->vehicle_id);

        if(is_array($request->detail)) {
            foreach($request->detail as $value) {
                $exist = null;
                if( ($value['id'] ?? null) ) {
                    $exist = DB::table('vehicle_maintenance_details')
                    ->whereId($value['id'])
                    ->first();
                }
                $qty_realisasi = $value['qty_realisasi'] ?? 0;
                $cost_realisasi = $value['cost_realisasi'] ?? 0;
                $qty_rencana = $value['qty_rencana'] ?? 0;
                $cost_rencana = $value['cost_rencana'] ?? 0;
                $total_realisasi = $qty_realisasi * $cost_realisasi;
                $total_rencana = $qty_rencana * $cost_rencana;


                if($exist) {
                    DB::table('vehicle_maintenance_details')
                    ->whereId($value['id'])
                    ->update([
                        'qty_realisasi' => $qty_realisasi,
                        'cost_realisasi' => $cost_realisasi,
                        'qty_rencana' => $qty_rencana,
                        'cost_rencana' => $cost_rencana,
                        'total_realisasi' => $total_realisasi,
                        'total_rencana' => $total_rencana
                    ]);

                } else {
                    DB::table('vehicle_maintenance_details')->insert([
                        'header_id' => $vm_id,
                        'vehicle_maintenance_type_id' => $value['vehicle_maintenance_type_id'],
                        'tipe_kegiatan' => $value['tipe_kegiatan'],
                        'item_id' => $value['item_id'] ?? null,
                        'rack_id' => $value['rack_id'] ?? null,
                        'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'] ?? null,
                        'cost_rencana' => $value['price'] ?? 0,
                        'qty_rencana' => $value['qty'] ?? 0,
                        'qty_realisasi' => $qty_realisasi,
                        'cost_realisasi' => $cost_realisasi,
                        'total_rencana' => $total_rencana,
                        'total_realisasi' => $total_realisasi
                    ]);
                }
            }
        }

    if ($ss->is_internal==0) {
      $pr=PurchaseRequest::create([
        'company_id' => $ss->company_id,
        'supplier_id' => $ss->vendor_id,
        'description' => 'Biaya Pemakaian Barang untuk Perawatan Kendaraan oleh Vendor - '.$ss->code.' - Nopol : '.$v->nopol,
        'create_by' => auth()->id(),
        'status' => 2,
        'code' => $ss->code,
        'date_needed' => dateDB($request->date_rencana),
        'date_request' => date("Y-m-d"),
        'vehicle_maintenance_id' => $ss->id
      ]);




      $det=VehicleMaintenanceDetail::where('header_id', $ss->id)->get();
      foreach ($det as $key => $value) {
        PurchaseRequestDetail::create([
          'header_id' => $pr->id,
          'vehicle_id' => $ss->vehicle_id,
          'item_id' => $value->item_id,
          'qty' => $value->qty_rencana,
          'qty_approve' => $value->qty_rencana,
          'vehicle_maintenance_detail_id' => $value->id,
        ]);
      }
    }

    UsingItem::where('vehicle_maintenance_id', $vm_id)->update([
      'date_approve' => date("Y-m-d"),
      'status' => 2
    ]);
    DB::commit();

    return response()->json(null);

  }

    public function store_selesai(Request $request, $vm_id)
    {
        $request->validate([
            'name' => 'required',
            'km_rencana' => 'required',
            'cost_rencana' => 'required',
            'date_rencana' => 'required',
        ]);

        DB::beginTransaction();
        $i=VehicleMaintenance::find($vm_id);

        if(is_array($request->detail)) {
            foreach($request->detail as $value) {
                $exist = null;
                if( ($value['id'] ?? null) ) {
                    $exist = DB::table('vehicle_maintenance_details')
                    ->whereId($value['id'])
                    ->first();
                    $qty_realisasi = $value['qty_realisasi'] ?? 0;
                    $cost_realisasi = $value['cost_realisasi'] ?? 0;
                    $qty_rencana = $value['qty_rencana'] ?? 0;
                    $cost_rencana = $value['cost_rencana'] ?? 0;
                    $total_realisasi = $qty_realisasi + $cost_realisasi;
                }
                if($exist) {
                    DB::table('vehicle_maintenance_details')
                    ->whereId($value['id'])
                    ->update([
                        'qty_realisasi' => $qty_realisasi,
                        'cost_realisasi' => $cost_realisasi,
                        'qty_rencana' => $qty_rencana,
                        'cost_rencana' => $cost_rencana,
                        'total_realisasi' => $total_realisasi
                    ]);

                } else {
                    DB::table('vehicle_maintenance_details')->insert([
                        'header_id' => $vm_id,
                        'vehicle_maintenance_type_id' => $value['vehicle_maintenance_type_id'],
                        'tipe_kegiatan' => $value['tipe_kegiatan'],
                        'item_id' => $value['item_id'] ?? null,
                        'rack_id' => $value['rack_id'] ?? null,
                        'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'] ?? null,
                        'cost_rencana' => $value['price'] ?? 0,
                        'qty_rencana' => $value['qty'] ?? 0,
                        'total_rencana' => $value['qty']*$value['price'],
                        'qty_realisasi' => $qty_realisasi,
                        'cost_realisasi' => $cost_realisasi,
                        'total_realisasi' => $total_realisasi
                    ]);
                }
            }
        }
    
        $vmd=VehicleMaintenanceDetail::where('header_id', $vm_id)->get();
        foreach ($vmd as $key => $value) {
            $terpakai=$value->qty_realisasi;
            $terpakaiCost=$value->cost_realisasi;
            $sisa=$value->qty_rencana-$value->qty_realisasi;

            $item = IT::show($value->item_id);
            // if($item->is_service == 0 && $i->warehouse_id) {
            if($item->is_service == 0) {
                if($i->is_internal == 1 && $terpakai > 0) {
                    UsingItemDetail::where('vehicle_maintenance_detail_id', $value->id)->update([
                        'used' => $terpakai,
                        'cost'=> $terpakaiCost,
                        'total' => $terpakai*$terpakaiCost
                    ]);
                }

                if ($sisa>0 && $i->is_internal==1) {
                  // kalau ada sisa maka dimasukkan ke gudang
                  $wrd = WarehouseReceiptDetail::find($value->warehouse_receipt_detail_id);
                  if($wrd){
                    $rack=Rack::find($wrd->rack_id);
                    if($rack){
                      StockTransaction::create([
                          'warehouse_receipt_detail_id' => $value->warehouse_receipt_detail_id,
                          'warehouse_id' => $rack->warehouse_id,
                          'rack_id' => $rack->id,
                          'item_id' => $value->item_id,
                          'type_transaction_id' => 18,
                          'code' => $i->code,
                          'date_transaction' => date("Y-m-d"),
                          'description' => "Sisa Barang dari Perawatan Kendaraan - ".$i->code,
                          'qty_masuk' => $sisa,
                          'harga_masuk' => $value->cost_rencana,
                      ]);
                    }
                  }
                }

                if($terpakai > $value->qty_rencana){
                  // kalau ada item melebihi perencanaan
                  $wrd = WarehouseReceiptDetail::find($value->warehouse_receipt_detail_id);
                  if($wrd){
                    $rack=Rack::find($wrd->rack_id);
                    if($rack){
                      StockTransaction::create([
                          'warehouse_receipt_detail_id' => $value->warehouse_receipt_detail_id,
                          'warehouse_id' => $rack->warehouse_id,
                          'rack_id' => $rack->id,
                          'item_id' => $value->item_id,
                          'type_transaction_id' => 18,
                          'code' => $i->code,
                          'date_transaction' => date("Y-m-d"),
                          'description' => "Tambahan Pemakaian Barang Untuk Perawatan Kendaraan - ". $i->code,
                          'qty_keluar' => $terpakai - $value->qty_rencana,
                          'harga_keluar' => $terpakaiCost,
                      ]);
                    }
                  }
                }

                $wsd = WarehouseStockDetail::where('warehouse_receipt_detail_id', $value->warehouse_receipt_detail_id)
                                          ->where('item_id', $value->item_id)
                                          ->first();

                if($wsd){
                  if($terpakai >= $value->qty_rencana){
                    WarehouseStockDetail::whereId($wsd->id)
                    ->update([
                      'qty' => $wsd->qty - $terpakai,
                      'onhand_qty' => $wsd->onhand_qty - ( $terpakai > $value->qty_rencana ? $terpakai : $value->qty_rencana )
                    ]);
                  } elseif($terpakai < $value->qty_rencana){
                    WarehouseStockDetail::whereId($wsd->id)
                    ->update([
                      'qty' => $wsd->qty - $value->qty_rencana,
                      'onhand_qty' => $wsd->onhand_qty - $value->qty_rencana
                    ]);
                  }
                }
            }
            
            $value->update([
                'qty_realisasi' => $terpakai,
                'cost_realisasi' => $terpakaiCost,
                'total_realisasi' => $terpakai*$terpakaiCost,
            ]);
        }

        $i->update([
            'vendor_id' => $request->vendor_id,
            'km_realisasi' => $request->km_rencana,
            'cost_realisasi' => $request->cost_rencana,
            'date_realisasi' => dateDB($request->date_rencana),
            'description' => $request->description,
            'status' => 5
        ]);

        UsingItem::where('vehicle_maintenance_id', $vm_id)->update([
            'status' => 4
        ]);

        DB::commit();
    }

  public function store_detail(Request $request, $vm_id)
  {
    // dd($request);
    $cek=VehicleMaintenanceDetail::where('header_id', $vm_id)->where('item_id', $request->item['id'])->count();
    if ($cek>0) {
      return response()->json(['message' => 'Item ini sudah dimasukkan'],500);
    }
    DB::beginTransaction();
    $head=VehicleMaintenance::find($vm_id);
    // dd($head);
    $i=VehicleMaintenanceDetail::create([
      'header_id' => $vm_id,
      'vehicle_maintenance_type_id' => $request->vehicle_maintenance_type_id,
      'tipe_kegiatan' => $request->tipe_kegiatan,
      'item_id' => $request->item['id'],
      'cost_rencana' => $request->price,
      'qty_rencana' => $request->qty,
      'total_rencana' => $request->price*$request->qty,
    ]);
    if ($head->is_internal==1) {
      $ui=UsingItem::where('vehicle_maintenance_id', $vm_id)->first();
      UsingItemDetail::create([
        'header_id' => $ui->id,
        'vehicle_maintenance_detail_id' => $i->id,
        'item_id' => $request->item['id'],
        'qty' => $request->qty,
        'cost' => $request->price,
        'total' => $request->price*$request->qty,
      ]);
    }
    DB::commit();

    return response()->json(null);
  }

  public function edit_detail(Request $request, $vmd_id)
  {
    DB::beginTransaction();
    $d=VehicleMaintenanceDetail::find($vmd_id);
    $head = VehicleMaintenance::find($d->header_id);
    if ($request->is_selesai) {
      ///jika selesai
      VehicleMaintenanceDetail::find($vmd_id)->update([
        'qty_realisasi' => $request->qty,
        'cost_realisasi' => $request->price,
        'total_realisasi' => $request->price*$request->qty,
      ]);

      if ($head->is_internal==1) {
        UsingItemDetail::where('vehicle_maintenance_detail_id', $vmd_id)->update([
          'used' => $request->qty,
          'cost' => $request->price,
          'total' => $request->price*$request->qty,
        ]);
      }
    } else {
      //jika masih rencana
      VehicleMaintenanceDetail::find($vmd_id)->update([
        'qty_rencana' => $request->qty,
        'cost_rencana' => $request->price,
        'total_rencana' => $request->price*$request->qty,
      ]);

      if ($head->is_internal==1) {
        UsingItemDetail::where('vehicle_maintenance_detail_id', $vmd_id)->update([
          'qty' => $request->qty,
          'cost' => $request->price,
          'total' => $request->price*$request->qty,
        ]);
      }

    }
    DB::commit();
  }

  public function delete_maintenance(Request $request)
  {
    DB::beginTransaction();
    DB::table('using_items')->where('vehicle_maintenance_id', $request->id)->delete();
    $pr=DB::table('purchase_requests')->where('vehicle_maintenance_id', $request->id)->first();
    if ($pr) {
      DB::table('purchase_orders')->where('purchase_request_id', $pr->id)->delete();
      DB::table('purchase_requests')->where('vehicle_maintenance_id', $request->id)->delete();
    }
    DB::table('vehicle_maintenances')->where('id', $request->id)->delete();
    DB::commit();

    return response()->json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function delete_detail($detail_id)
  {
    DB::beginTransaction();
    VehicleMaintenanceDetail::find($detail_id)->delete();
    DB::commit();

    return response()->json(null);
  }

  /**
   * Deskripsi: 
   */
  public function go_perawatan(Request $request, $vm_id)
  {
    // dd($request);
    DB::beginTransaction();
    $vm=VehicleMaintenance::find($vm_id);
    if ($vm->is_internal==0) {
    } else {
      $tp=TypeTransaction::where('slug','maintenance')->first();
      $vmd=VehicleMaintenanceDetail::where('header_id', $vm_id)
      ->whereNotNull('vehicle_maintenance_details.warehouse_receipt_detail_id')
      ->leftJoin("racks", 'racks.id', 'vehicle_maintenance_details.rack_id')
      ->get();
      // dd($tp);
      $rack=Rack::where('warehouse_id',$request->warehouse_id)->first();
      foreach ($vmd as $key => $value) {
        StockTransaction::create([
          'warehouse_receipt_detail_id' => $value->warehouse_receipt_detail_id,
          'warehouse_id' => $value->warehouse_id,
          'rack_id' => $value->rack_id,
          'item_id' => $value->item_id,
          'type_transaction_id' => $tp->id,
          'code' => $vm->code,
          'date_transaction' => date("Y-m-d"),
          'description' => "Pemakaian Barang Untuk Perawatan Kendaraan - ".$vm->code,
          'qty_keluar' => $value->qty_rencana,
          'harga_keluar' => $value->cost_rencana,
        ]);
        // setiap insert ST akan trigger DB, berefek ke WSD
      }
      $vm->update([
        'warehouse_id' => $request->warehouse_id
      ]);
    }

    $vm->update([
      'date_perawatan' => date("Y-m-d"),
      'status' => 4
    ]);

    UsingItem::where('vehicle_maintenance_id', $vm_id)->update([
      'date_pemakaian' => date("Y-m-d"),
      'status' => 3
    ]);
    DB::commit();

    return response()->json(null);
  }

  public function store_item_detail(Request $request,$vmd_id)
  {
    $request->validate([
      'qty' => 'required',
      'price' => 'required',
    ]);
    DB::beginTransaction();
    $i=VehicleMaintenanceDetail::find($vmd_id);
    if ($i->header->status<4) {
      $i->update([
        'qty_rencana' => $request->qty,
        'cost_rencana' => $request->price,
        'total_rencana' => $request->price*$request->qty,
      ]);
    } else {
      $i->update([
        'qty_realisasi' => $request->qty,
        'cost_realisasi' => $request->price,
        'total_realisasi' => $request->price*$request->qty,
      ]);
    }
    DB::commit();

    return response()->json(null);

  }
}

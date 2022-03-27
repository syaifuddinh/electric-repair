<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Model\Rack;
use App\Model\StorageType;
use App\Model\Contact;
use App\Model\Company;
use App\Model\City;
use App\Model\Item;
use App\Model\VehicleType;
use App\Model\WarehouseReceipt;
use App\Abstracts\WarehouseReceipt AS WR;
use App\Abstracts\Inventory\WarehouseReceiptDetail AS WRD;
use App\Model\WarehouseReceiptDetail;
use App\Model\WarehouseStockDetail;
use App\Model\Warehouse;
use App\Model\Piece;
use App\Model\DeliveryOrderPhoto;
use App\Abstracts\Inventory\PurchaseOrderReceiptDetail;
use App\Utils\TransactionCode;
use App\Http\Controllers\Operational\JobOrderController;
use File;
use DB;
use Response;
use Carbon\Carbon;
use Image;
use bPDF;
use Illuminate\Support\Str;
use Exception;

class ReceiptController extends Controller
{
    /*
      Date : 16-03-2021
      Description : Kirim email
      Developer : Didin
      Status : Create
    */
    public function sendEmail($id)
    {
        $status_code = 200;
        $msg = 'Data successfully sent';
        try {
            WR::sendEmail($id);
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 16-03-2021
      Description : Kirim email
      Developer : Didin
      Status : Create
    */
    public function previewEmail($id)
    {
        $status_code = 200;
        $msg = 'OK';
        $data['data'] = [];
        DB::beginTransaction();
        try {
            $dt = WR::previewEmail($id);
            $data['data'] = $dt;
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return $data['data'];
    }


    /*
      Date : 15-03-2020
      Description : Menampilkan semua no ttb
      Developer : Didin
      Status : Create
    */
    public function index()
    {
        $warehouse_receipt = DB::table('warehouse_receipts')
        ->whereStatus(1)
        ->select('id', 'code', 'warehouse_id', 'customer_id')
        ->orderBy('id', 'desc')
        ->get();

        return Response::json($warehouse_receipt,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 02-03-2020
      Description : Menampilkan semua rak penyimpanan
      Developer : Didin
      Status : Create
    */
    public function rack()
    {
        $racks = DB::table('racks')
        ->select('id', 'code', 'warehouse_id')
        ->get();

        return Response::json($racks,200,[],JSON_NUMERIC_CHECK);
    }


    /*
      Date : 15-03-2020
      Description : Menampilkan semua gudang
      Developer : Didin
      Status : Create
    */
    public function warehouse()
    {
        $racks = DB::table('warehouses')
        ->select('id', 'code', 'name', 'company_id')
        ->get();

        return Response::json($racks,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['staff']=Contact::whereRaw("is_staff_gudang = 1")->select('id','name','address','company_id')->get();
      $data['category']= DB::table('categories')->where('is_jasa', 0)->get();
      $data['vehicle_type']=VehicleType::select('id', 'name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function createImageFromBase64($image){ 
       $file_data = $image; 
       $extension = preg_replace('/data:image\/(\w+);base64.+/', '$1', $file_data);
       $file_name = 'image_'.time() . '.' . $extension; //generating unique file name; 
       @list($type, $file_data) = explode(';', $file_data);
       @list(, $file_data) = explode(',', $file_data); 
       if($file_data!=""){ // storing image in storage/app/public Folder 
            Storage::disk('public')->put($file_name,base64_decode($file_data)); 
            return $file_name; 
       }
       else {
        return false;
       }

    }

    /*
      Date : 29-03-2020
      Description : Menyimpan penerimaan barang
      Developer : Didin
      Status : Edit
  */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'receive_date' => 'required',
            'receive_time' => 'required'
        ]);
        $status_code = 200;
        $msg = 'Data successfully saved';

        DB::beginTransaction();

        try {
            WR::store($request->all());
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            $status_code = 421;
        }


        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      WarehouseReceiptDetail::find($id)->delete();
      DB::commit();
    }

    /*
      Date : 01-04-2020
      Description : Meng-update detail penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function update_detail(Request $request, $id)
    {
        $dt['message'] = 'OK';
        $status_code = 200;
        DB::beginTransaction();
        try {
            WRD::update($request->all(), $id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $dt['message'] = $e->getMessage();
        }
        DB::commit();
        return Response::json($dt, $status_code);
    }

    /*
      Date : 01-04-2020
      Description : Menyimpan detail penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function store_detail(Request $request, $id)
    {
      DB::beginTransaction();
      $piece = Piece::where('name', 'Item')->first();
      $piece_id = $piece->id;
      if($request->storage_type == 'HANDLING') {
        // Validasi handling area
      $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $request->warehouse_id);
      $handling_is_exists = $handling_storage->count();
      if($handling_is_exists == 0) {
        $r = Rack::create([
          'warehouse_id' => $request->warehouse_id,
          'barcode' => '-',
          'code' => 'Handling Area',
          'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
          'capacity_volume' => 100000,
          'capacity_tonase' => 100000
        ]);

        $rack_id = $r->id;
      }
      else {
        $r = $handling_storage->selectRaw('racks.id AS id')->first();
        $rack_id = $r->id;
      }

      } 
      WarehouseReceiptDetail::create([
          'header_id' => $id,
          'storage_type' => $request->storage_type,
          'rack_id' => $request->storage_type == 'RACK' ? $request->rack_id : $rack_id,
          'vehicle_type_id' => $request->vehicle_type_id??null,
          'piece_id_2' => $request->piece_id_2 ?? $piece_id,
          'qty_2' => $request->qty_2??0,
          'piece_id' => $request->piece_id ?? $piece_id,
          'qty' => $request->qty??0,
          'weight' => $request->weight??0,
          'volume' => ($request->long??0 * $request->wide??0 * $request->high??0),
          'long' => $request->long??0,
          'wide' => $request->wide??0,
          'high' => $request->high??0,
          'imposition' => $request->imposition??1,
          'item_id' => $request->item_id??null,
          'item_name' => $request->item_name??null,
          'kemasan' => $request->kemasan??null,
          'pallet_id' => $request->pallet_id??null,
          'pallet_qty' => $request->pallet_qty??0,
          
          'create_by' => auth()->id()
        ]);

        // WarehouseReceiptDetail::update_item();
      DB::commit();
    }

    /*
      Date : 16-03-2020
      Description : Menampilkan detail penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
      $data['item']= WR::show($id);
      $warehouse_receipt_code = $data['item']->code;

      $url = url('operational_warehouse/receipt/detail/');

      $data['detail']=WarehouseReceiptDetail::with('piece:id,name','vehicle_type:id,name', 'rack', 'pallet')
      ->leftJoin('pieces AS pieces', 'pieces.id', 'warehouse_receipt_details.piece_id')
      ->leftJoin('pieces AS pieces2', 'pieces2.id', 'warehouse_receipt_details.piece_id_2')
      ->where('warehouse_receipt_details.header_id', $id)
      ->selectRaw(
        'warehouse_receipt_details.*,
        pieces.name AS piece_name,
        pieces2.name AS piece_name_2,
        CONCAT("' . $url .'", "/", warehouse_receipt_details.id, "/barcode") AS barcode_url,
        IFNULL((SELECT sum(qty) FROM warehouse_stock_details WHERE item_id = warehouse_receipt_details.item_id AND warehouse_stock_details.warehouse_receipt_id = "' . $id . '"), 0) AS stock'
      )
      ->get();
      $path = asset('files') . '/';
      $data['surat_jalan']=DeliveryOrderPhoto::where('receipt_id', $id)->selectRaw("CONCAT('$path', name) AS name")->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 24-03-2020
      Description : Menampilkan job order yang terikat dengan satu 
                    penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function showJobOrder($id)
    {
        $data = DB::table('job_orders AS J')
        ->join('job_order_details AS JD', 'JD.header_id', 'J.id')
        ->join('warehouse_receipt_details AS WD', 'WD.id', 'JD.warehouse_receipt_detail_id')
        ->join('warehouse_receipts AS W', 'W.id', 'WD.header_id')
        ->join('kpi_statuses AS K', 'K.id', 'J.kpi_id')
        ->where('W.id', $id)
        ->groupBy('J.id')
        ->select('J.id', 'J.code', 'K.name AS kpi_status_name', 'J.customer_id')
        ->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 24-03-2020
      Description : Menampilkan job order pengiriman yang terikat dengan satu 
                    penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function showJobOrderPengiriman($customer_id)
    {
        $data = DB::table('job_orders AS J')
        ->whereCustomerId($customer_id)
        ->whereIn('service_type_id', [1, 2, 3, 4])
        ->select('J.id', 'J.code')
        ->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 24-03-2020
      Description : Menampilkan packing list yang terikat dengan satu 
                    penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function showManifest($id)
    {
        $data = DB::table('manifest_details AS MD')
        ->join('job_order_details AS JD', 'JD.id', 'MD.job_order_detail_id')
        ->join('manifests AS M', 'M.id', 'MD.header_id')
        ->leftJoin('containers AS C', 'C.id', 'M.container_id')
        ->join('warehouse_receipt_details AS WD', 'WD.id', 'JD.warehouse_receipt_detail_id')
        ->join('warehouse_receipts AS W', 'W.id', 'WD.header_id')
        ->where('W.id', $id)
        ->groupBy('M.id')
        ->select(
            'M.id', 
            'M.code',
            'M.is_container',
            DB::raw('IF(M.is_container = 0, depart, C.stripping) AS departure'),
            DB::raw('IF(M.is_container = 0, arrive, C.stuffing) AS arrival')
          )
        ->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 02-03-2020
      Description : Menampilkan detail penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function showDetail($id)
    {
        $data['detail']= WarehouseReceiptDetail::with('piece:id,name','vehicle_type:id,name', 'rack:id,code,capacity_tonase,capacity_volume', 'pallet:id,name')
       ->leftJoin('pieces AS pieces', 'pieces.id', 'warehouse_receipt_details.piece_id')
      ->leftJoin('pieces AS pieces2', 'pieces2.id', 'warehouse_receipt_details.piece_id_2')
        ->whereHeaderId($id)
        ->selectRaw('
            warehouse_receipt_details.*, 
            pieces.name AS piece_name,
            pieces2.name AS piece_name_2,
            IFNULL((SELECT sum(qty) FROM warehouse_stock_details WHERE item_id = warehouse_receipt_details.item_id AND warehouse_stock_details.warehouse_receipt_id = "' . $id . '"), 0) AS stock')
        ->get();
        
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }


    /*
      Date : 16-03-2020
      Description : Menampilkan barcode pada tiap detail penerimaan 
                    barang
      Developer : Didin
      Status : Edit
    */
    public function showBarcode($warehouse_receipt_detail_id)
    {
        $data['code'] = WRD::getBarcode($warehouse_receipt_detail_id); 
        return view('operational_warehouse.barcode', $data);
    }

    public function print($id)
    {
      $data['item']=WarehouseReceipt::with('customer.company','collectible','warehouse','staff')->where('id', $id)->first();
      $data['item']->ttd = $data['item']->ttd ? asset('files') . '/' . $data['item']->ttd : null;
      $data['item']->prefix = preg_replace('/(.+\/)(\d+)$/', '$1', $data['item']->code);
      $data['item']->suffix = preg_replace('/(.+\/)(\d+)$/', '$2', $data['item']->code);
      $data['detail']=WarehouseReceiptDetail::with('piece','vehicle_type', 'rack')->where('header_id', $id)->get();
      $data['surat_jalan']=DeliveryOrderPhoto::where('receipt_id', $id)->get();
      return view('operational_warehouse/surat_terima_barang', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */ 
    public function edit($id)
    {
      $data['item']=WarehouseReceipt::with('customer', 'company', 'collectible','warehouse','staff')->find($id);
      $data['item']->ttd = $data['item']->ttd ? asset('files') . '/' . $data['item']->ttd : null; 
      $data['company']=companyAdmin(auth()->id());
      $data['staff']=Contact::where('is_staff_gudang', 1)->select('id','name')->get();
      $data['warehouse']=Warehouse::where('is_active', 1)->get();
      $data['vehicle_type']=VehicleType::select('id', 'name')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 31-03-2020
      Description : Meng-update detail job order dengan detail penerimaan 
                    barang
      Developer : Didin
      Status : Create
    */
    public function updateJobOrderPengiriman($oldest_job_order_id, $new_job_order_id, $warehouse_receipt_id)  
    {
        if($oldest_job_order_id != $new_job_order_id) {
            $details = DB::table('warehouse_receipt_details')
            ->whereHeaderId($warehouse_receipt_id)
            ->get();

            $jo = DB::table('job_orders')
            ->whereId($new_job_order_id)
            ->first();

            $wod = DB::table('work_order_details')
            ->whereId($jo->work_order_detail_id)
            ->first();
            if($wod->price_list_id != null) {
                $price_list = DB::table('price_lists')
                ->whereId($wod->price_list_id)
                ->first();
                $price_volume = $price_list->price_volume;
                $price_tonase = $price_list->price_tonase;
                $price_item = $price_list->price_item;
                $price_borongan = $price_list->price_full;
            } else {
                $quotation_detail = DB::table('quotation_details')
                ->whereId($wod->quotation_detail_id)
                ->first();
                $price_volume = $quotation_detail->price_contract_volume;
                $price_tonase = $quotation_detail->price_contract_tonase;
                $price_item = $quotation_detail->price_contract_item;
                $price_borongan = $quotation_detail->price_contract_full;
            }

            foreach($details as $detail) {
                $volume = ($detail->long * $detail->wide * $detail->high / 1000000) * $detail->qty;
                switch($detail->imposition) {
                    case 1:
                        $price = $price_volume;
                        $total_price = $price_volume * $volume;
                        break;

                    case 2:
                        $price = $price_tonase;
                        $total_price = $price_tonase * $detail->weight * $detail->qty;
                        break;

                    case 3:
                        $price = $price_item;
                        $total_price = $price_item * $detail->qty;
                        break;

                    case 4:
                        $price = $price_borongan;
                        $total_price = $price_borongan;
                        break;


                }

                $volume = (string) $volume;
                DB::table('job_order_details')
                ->insert([
                    'volume' => $volume,
                    'header_id' => $new_job_order_id,
                    'create_by' => auth()->id(),
                    'item_id' => $detail->item_id,
                    'item_name' => $detail->item_name,
                    'piece_id' => $detail->piece_id,
                    'imposition' => $detail->imposition,
                    'qty' => $detail->qty,
                    'long' =>  $detail->long,
                    'wide' =>  $detail->wide,
                    'high' =>  $detail->high,
                    'rack_id' =>  $detail->rack_id,
                    'warehouse_receipt_detail_id' =>  $detail->id,
                    'weight' => $detail->weight * $detail->qty,
                    'price' => $price ?? 0,
                    'total_price' => $total_price
                ]);
                $jobOrderController = new JobOrderController();
                $jobOrderController->storeShipmentStatus($detail->id);
            }
        }
    }
    /*
      Date : 31-03-2020
      Description : Meng-update penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function update(Request $request, $id)
    {
      if(!isset($request->company_id)) {
        return Response::json(["message" => "Cabang tidak boleh kosong"], 422);
      }
      if(!isset($request->receive_date)) {
        return Response::json(["message" => "Tanggal terima tidak boleh kosong"], 422);
      }
      if(!isset($request->receive_time)) {
        return Response::json(["message" => "Waktu terima tidak boleh kosong"], 422);
      }
      DB::beginTransaction();
      $i=WarehouseReceipt::find($id);
      $ttd_origin = $i->ttd;
      if($request->filled('job_order_pengiriman_id')) {
          $this->updateJobOrderPengiriman($i->job_order_pengiriman_id, $request->job_order_pengiriman_id, $id);
      }
      $inputs = [
        'is_overtime' => $request->is_overtime ?? 0,
        'company_id' => $request->company_id,
        'customer_id' => $request->customer_id,
        'job_order_pengiriman_id' => $request->job_order_pengiriman_id,
        'warehouse_id' => $request->warehouse_id,
        'city_to' => $request->city_to,
        'sender' => $request->sender,
        'receiver' => $request->receiver ?? 'N/C',
        'reff_no' => $request->reff_no,
        'receive_date' => createTimestamp($request->receive_date,$request->receive_time),
        'is_export' => $request->is_export,
        'description' => $request->description,
        'create_by' => auth()->id(),
        'nopol' => $request->nopol,
        'driver' => $request->driver,
        'receipt_type_id' => $request->receipt_type_id,
        'phone_number' => $request->phone_number,
        'vehicle_type_id' => $request->vehicle_type_id,
        'package' => $request->input('package', null),
        // 'description' => $request->description,
      ];
      WR::storePenerima(($request->receiver ?? 'N/C'), $request->city_to, $request->company_id);
      if(isset($request->ttd)) {
          $ttd_file = $request->file('ttd');
          $ext = $ttd_file->getClientOriginalExtension();

          if( $ext == null OR $ext == '') {
            $ext = 'png';
          }
          $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.' . $ext;
          Image::make( $ttd_file->getRealPath() )->save(public_path('files/' . $ttd));
          $inputs['ttd'] = $ttd;
          File::delete(public_path('files/' . $ttd_origin));
      }
      $i->fill($inputs);
      $i->save();
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
        $warehouse_receipt = DB::table('warehouse_receipts')
        ->whereId($id)
        ->first();
        if($warehouse_receipt !== null) {
            if($warehouse_receipt->status == 1) {              
                return Response::json(['message' => 'Data yang sudah disetujui tidak dapat dihapus'], 421);
            }
            try {
                if($warehouse_receipt->status == 0) {
                    PurchaseOrderReceiptDetail::clearByReceipt($id);
                    DB::table('warehouse_receipts')
                    ->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
                    ->where('warehouse_receipts.id', $id)
                    ->delete();
                } else if($warehouse_receipt->status == 2) {
                    DB::table('warehouse_receipts')
                    ->where('warehouse_receipts.id', $id)
                    ->update([
                        'deleted_at' => DB::raw('NOW()') 
                    ]);
                }
            } catch (Exception $e) {
                return Response::json(['message' => 'Data tidak bisa dihapus karena sudah digunakan'], 421);
            }
        } else {
            return Response::json(['message' => 'Data tidak ditemukan'], 404);
        }
    }

    public function approve($id)
    {
        WR::validateIsApproved($id);
        $details = DB::table('warehouse_receipt_details')
        ->whereHeaderId($id)
        ->count('id');
        if($details == 0) {
            return Response::json(['message' => 'Detail barang tidak boleh kosong'], 421);
        }
        $attachments = DB::table('delivery_order_photos')->whereReceiptId($id)->count('id');
        if($attachments < 1) {
           return Response::json(['message' => 'Lampiran wajib diisi jika penerimaan barang mau disetujui'], 500);
        } else {

            DB::beginTransaction();
            $wd = WarehouseReceiptDetail::where('header_id', $id)->get();
            $w = WarehouseReceipt::find($id);
            if($w->customer_id) {
                $customer = DB::table('contacts')
                ->whereId($w->customer_id)
                ->count();
                if($customer == 0) {
                    return Response::json(['message' => 'Customer tidak boleh kosong'], 421);              
                }
            }
            $option = ['warehouse_id' => $w->warehouse_id];
            $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($wd, $option);
            $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($wd, $option);
            if($cek_kapasitas !== true) {
              return $cek_kapasitas;
            }
            else if($cek_pallet_keluar !== true){
              return $cek_pallet_keluar;
            }

            $w->status = 1;
            $detail = WarehouseReceiptDetail::where("header_id", $id)->whereRaw('item_id IS NOT NULL')->get();

            // Meng-update dimensi dan berat di master item
            foreach ($detail as $value) {
              # code...
              $item = Item::find($value->item_id);
              if($item == null) {
                  DB::table('warehouse_receipt_details')
                  ->whereId($value->id)
                  ->update([
                      'item_id' => null
                  ]);
                  WarehouseReceiptDetail::update_item();
                  $item = DB::table('items') 
                  ->join('warehouse_receipt_details', 'warehouse_receipt_details.item_id', 'items.id')
                  ->where('warehouse_receipt_details.id', $value->id);

              } 
              $item->update([
                  'items.wide' => $value->wide ,
                  'items.long' => $value->long ,
                  'items.height' => $value->high ,
                  'items.volume' => $value->wide * $value->long * $value->high ,
                  'items.tonase' => $value->weight ,
              ]);
            }
            $w->save();
            DB::commit();

            return Response::json(['message' => 'Data successfully approved']);
        }
    }

    public function show_attachment($receipt_id) {
        $attachment = WR::showAttachment($receipt_id);

        return Response::json($attachment);
    }

    public function destroy_attachment($receipt_id, $delivery_order_photo_id) {
        WarehouseReceipt::findOrFail($receipt_id);
        $photo = DeliveryOrderPhoto::findOrFail($delivery_order_photo_id);
        File::delete(public_path('files/' . $photo->name));
        $photo->delete();

        return Response::json(['message' => 'Lampiran berhasil dihapus']);
    }

    public function store_attachment(Request $request, $receipt_id) {
        WarehouseReceipt::findOrFail($receipt_id);
        if($request->has('files')) {
            $attachments = [];
            $file=$request->file('files');
            $c = 0;

            foreach($file as $image) {
              $origin = $image->getClientOriginalName();
              $filename = 'LAMPIRAN_PENERIMAAN_BARANG' . date('Ymd_His') . $c;
              $img = Image::make($image->getRealPath());
              $img->resize(600, null, function ($constraint) {
                  $constraint->aspectRatio();
              })->save(public_path('files/' . $filename));
              $c++;
              $id = DB::table('delivery_order_photos')->insertGetId([
                  'receipt_id' => $receipt_id,
                  'name' => $filename
              ]);
              array_push($attachments, [
                  'id' => $id,
                  'name' => asset("files/$filename")
              ]);
            }
        } else {
            return Response::json(['message' => 'File wajib dilampirkan'], 500);
        }

        return Response::json(['message' => 'File berhasil di-upload', 'attachments' => $attachments]);
    }

    /*
      Date : 28-07-2020
      Description : Membatalkan penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function cancel($id) {
        $existing = DB::table('warehouse_receipts')
        ->whereId($id)
        ->count();

        if($existing == 0) {
            return Response::json(['message' => 'Data tidak ditemukan'], 404);
        } else {
            if($this->hasJobOrder($id)) {
                return Response::json(['message' => 'Penerimaan barang tidak dapat dihapus karena sudah mempunyai job order'], 421);                
            }

            if($this->hasItemMigration($id)) {
                return Response::json(['message' => 'Penerimaan barang tidak dapat dihapus karena sudah mempunyai mutasi transfer atau putaway'], 421);                
            }

            if(!$this->sameAsFirstTime($id)) {
                return Response::json(['message' => 'Penerimaan barang tidak dapat dihapus karena sudah mempunyai jumlah barang ketika awal diterima tidak sama dengan jumlah barang sekarang'], 421);                
            }
            DB::beginTransaction();
            try {
                $this->rollback($id);
                DB::commit();
            } catch(Exception $e) {
                DB::rollback();
                return Response::json(['message' => $e->getMessage()], 421);
            }
        }

        return Response::json(['message' => 'Pembatalan penerimaan barang berhasil dibatalkan']);
    }

    /*
      Date : 28-07-2020
      Description : Membatalkan penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function rollback($id) {
        // Rollback in warehouse stock
        $warehouse_receipt = DB::table('warehouse_receipts')
        ->whereId($id)
        ->select('warehouse_id', 'code')
        ->first();

        $stockTransaction = DB::raw("(SELECT stock_transactions.warehouse_receipt_detail_id, SUM(stock_transactions.qty_masuk - stock_transactions.qty_keluar) AS qty_sisa FROM stock_transactions JOIN warehouse_receipt_details ON warehouse_receipt_details.id = stock_transactions.warehouse_receipt_detail_id WHERE warehouse_receipt_details.header_id = $id GROUP BY stock_transactions.warehouse_receipt_detail_id) AS stock_transactions");

        $itemsInWarehouse = DB::table('warehouse_receipt_details')
        ->leftJoin($stockTransaction, 'stock_transactions.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
        ->where('warehouse_receipt_details.header_id', $id)
        ->select('stock_transactions.qty_sisa', 'id', 'item_id', 'rack_id')
        ->get();
        foreach($itemsInWarehouse as $i) {
            DB::table('stock_transactions')->insert([
              'warehouse_id' => $warehouse_receipt->warehouse_id,
              'rack_id' => $i->rack_id,
              'item_id' => $i->item_id,
              'type_transaction_id' => 32,
              'code' => $warehouse_receipt->code,
              'date_transaction' => Carbon::now(),
              'description' => 'Pembatalan penerimaan barang  - '. $warehouse_receipt->code,
              'qty_keluar' => $i->qty_sisa ?? 0,
              'warehouse_receipt_detail_id' => $i->id
            ]);
        }

        DB::table('warehouse_receipts')
        ->whereId($id)
        ->update([
            'status' => 2
        ]);
    }

    public function hasJobOrder($warehouse_receipt_id) {
        $existing = DB::table('job_order_details')
        ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
        ->where('warehouse_receipt_details.header_id', $warehouse_receipt_id)
        ->count('job_order_details.id');

        if($existing > 0) {
            return true;
        }

        return false;
    }

    public function hasItemMigration($warehouse_receipt_id) {
        $existing = DB::table('item_migration_details')
        ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'item_migration_details.warehouse_receipt_detail_id')
        ->where('warehouse_receipt_details.header_id', $warehouse_receipt_id)
        ->count('item_migration_details.id');

        if($existing > 0) {
            return true;
        }

        return false;
    }

    public function sameAsFirstTime($warehouse_receipt_id) {
        $stock_transactions = '(SELECT warehouse_receipt_detail_id, item_id,  SUM(qty_masuk - qty_keluar) AS qty_sisa FROM stock_transactions GROUP BY warehouse_receipt_detail_id) AS stock_transactions';
        $received = DB::table('warehouse_receipt_details')
        ->whereHeaderId($warehouse_receipt_id)
        ->sum('qty');

        $existing = DB::table('warehouse_receipt_details')
        ->join(DB::raw($stock_transactions), 'stock_transactions.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
        ->where('warehouse_receipt_details.header_id', $warehouse_receipt_id)
        ->sum('qty_sisa');
        if($existing == $received) {
            return true;
        }

        return false;
    }

    /*
      Date : 16-03-2021
      Description : Download import item
      Developer : Didin
      Status : Create
    */
    public function downloadImportItem() {
        try {
            return WRD::downloadImportItemExample();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
            $data['message'] = $msg;
            return response()->json($data, $status_code);
        }

    }
    /*

      Date : 16-03-2021
      Description : Download import item
      Developer : Didin
      Status : Create
    */
    public function importItem(Request $request) {
        $status_code = 200;
        $msg = 'Data successfully imported';
        DB::beginTransaction();
        try {
            $is_pallet = $request->is_pallet == 1 ? true : false;
            $dt = WRD::importItem($request->file('file'), $request->warehouse_id, $is_pallet);
            $data['data'] = $dt;
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}

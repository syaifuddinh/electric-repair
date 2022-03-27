<?php
namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Rack;
use App\Model\StorageType;
use App\Model\Contact;
use App\Model\Company;
use App\Model\City;
use App\Model\Item;
use App\Model\VehicleType;
use App\Model\WarehouseReceipt;
use App\Model\WarehouseReceiptDetail;
use App\Model\WarehouseStockDetail;
use App\Model\Warehouse;
use App\Model\Piece;
use App\Model\DeliveryOrderPhoto;
use App\Utils\TransactionCode;
use DB;
use File;
use Response;
use Carbon\Carbon;
use Image;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Exception;
use App\Abstracts\WarehouseReceipt AS WR;

class ReceiptController extends Controller
{
    /*
      Date : 16-04-2020
      Description : Menambah lampiran pada penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function storeAttachment(Request $request, $receipt_id) {
        $receipt = DB::table('warehouse_receipts')
        ->whereId($receipt_id)
        ->first();
        if($receipt == null) {
            return Response::json(['status' => 'ERROR', 'message' => 'Penerimaan barang tidak ditemukan'], 422);
        } else {
            if($receipt->status == 1) {
                return Response::json(['status' => 'ERROR', 'message' => 'Lampiran tidak bisa ditambahkan pada penerimaan barang yang sudah disetujui'], 422);
            }
        }

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
            return Response::json(['status' => 'ERROR','message' => 'File wajib dilampirkan'], 500);
        }

        return Response::json(['status' => 'OK', 'message' => 'File berhasil di-upload', 'attachments' => $attachments]);
    }

    public function destroyAttachment($receipt_id, $delivery_order_photo_id) {
        $receipt = DB::table('warehouse_receipts')
        ->whereId($receipt_id)
        ->first();
        if($receipt == null) {
            return Response::json(['status' => 'ERROR', 'message' => 'Penerimaan barang tidak ditemukan'], 422);
        } else {
            if($receipt->status == 1) {
                return Response::json(['status' => 'ERROR', 'message' => 'Lampiran tidak bisa ditambahkan pada penerimaan barang yang sudah disetujui'], 422);
            }
        }



        $photo = DeliveryOrderPhoto::find($delivery_order_photo_id);
        if($photo == null) {
            return Response::json(['status' => 'ERROR', 'message' => 'Lampiran tidak ditemukan'], 422);
        }
        File::delete(public_path('files/' . $photo->name));
        $photo->delete();

        return Response::json(['status' => 'OK', 'message' => 'Lampiran berhasil dihapus']);
    }

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
        $data['company'] = companyAdmin(auth()->id());
        $data['supplier'] = Contact::where('is_supplier', 1)->select('id', 'name', 'company_id')->get();
        $data['customer'] = Contact::where('is_pelanggan', 1)->select('id', 'name', 'company_id')->get();
        $data['staff'] = Contact::whereRaw("is_staff_gudang = 1")->select('id', 'name', 'address', 'company_id')->get();
        $data['piece'] = Piece::all();
        $data['category'] = DB::table('categories')->where('is_jasa', 0)->get();
        $data['warehouse'] = Warehouse::where('is_active', 1)->get();
        $data['rack'] = Rack::join('storage_types', 'storage_type_id', '=', 'storage_types.id')->where('is_picking_area', 0)->where('is_handling_area', 0)->select('racks.id', DB::raw("CONCAT(code,' (',IFNULL(description,''),')') as name"), 'warehouse_id', 'capacity_volume', 'capacity_tonase')->get();
        $data['vehicle_type'] = VehicleType::all();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function createImageFromBase64($image)
    {
        $file_data = $image;
        $extension = preg_replace('/data:image\/(\w+);base64.+/', '$1', $file_data);
        $file_name = 'image_' . time() . '.' . $extension; //generating unique file name; 
        @list($type, $file_data) = explode(';', $file_data);
        @list(, $file_data) = explode(',', $file_data);
        if ($file_data != "") { // storing image in storage/app/public Folder 
            Storage::disk('public')->put($file_name, base64_decode($file_data));
            return $file_name;
        } else {
            return false;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_backup(Request $request)
    {
        if (!isset($request->company_id)) {
            return Response::json(["message" => "Cabang tidak boleh kosong"], 422);
        }
        if (!isset($request->customer_id)) {
            return Response::json(["message" => "Customer tidak boleh kosong"], 422);
        }
        if (!isset($request->warehouse_id)) {
            return Response::json(["message" => "Gudang tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_date)) {
            return Response::json(["message" => "Tanggal terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_time)) {
            return Response::json(["message" => "Waktu terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receiver)) {
            return Response::json(["message" => "Consignee tidak boleh kosong"], 422);
        }

        try {

        if (is_string($request->detail)) {

            $detail = json_decode($request->detail);
        } else {
            $detail = $request->detail;
        }

        if (!isset($detail)) {
            return Response::json(['message' => 'Item penerimaan barang tidak boleh kosong'], 422);
        }

        foreach ($detail as $x => $value) {
            if (empty($value)) {
                continue;
            }

            $detail[$x] = (object) $value;
        }

        if (!$request->has('files')) {
            return Response::json(['message' => 'Lampiran tidak boleh kosong'], 422);
        }

        if ($request->status == 1) {
            $option = [
                'warehouse_id' => $request->warehouse_id
            ];
            $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($detail, $option);
            $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($detail, $option);
            if ($cek_kapasitas !== true) {
                return $cek_kapasitas;
            } else if ($cek_pallet_keluar !== true) {
                return $cek_pallet_keluar;
            }
        }

        DB::beginTransaction();
        $code = new TransactionCode($request->company_id, 'warehouseReceipt');
        $code->setCode();
        $trx_code = $code->getCode();
        $file = $request->input('files');
        $attachment = [];
        $c = 0;
        $image_extension = '';
        foreach ($file as $image) {
            if (preg_match('/^(data\:image\/([a-zA-Z0-9]+);base64,)[a-zA-Z0-9=\/+\s]+$/', $image) == false) {
                return Response::json(['message' => 'Semua lampiran harus berformat base64'], 422);
            }
            $file = file_get_contents($image);
            $filename = 'LAMPIRAN_PENERIMAAN_BARANG' . date('Ymd_His') . $c . '.png';
            array_push($attachment, $filename);
            $img = Image::make($file);
            $img->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path('files/' . $filename));
            $c++;
        }

        if ($request->has('ttd')) {
            $ttd_file = $request->input('ttd');
            if (preg_match('/^(data\:image\/([a-zA-Z0-9]+);base64,)[a-zA-Z0-9=\/+\s]+$/', $ttd_file) == false) {
                return Response::json(['message' => 'Tanda tangan harus berformat base64'], 422);
            }
            $ttd_file_content = file_get_contents($ttd_file);
            $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.png';
            $img = Image::make(file_get_contents($ttd_file))->save(
                public_path('files') . '/' . $ttd
            );
        } else {
            $ttd = null;
        }

        $i = WarehouseReceipt::create([
            'company_id' => $request->company_id,
            'customer_id' => $request->customer_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'warehouse_id' => $request->warehouse_id,
            'city_to' => $request->city_to,
            'sender' => $request->sender,
            'receiver' => $request->receiver,
            'warehouse_staff_id' => auth()->id(),
            'reff_no' => $request->reff_no,
            'code' => $trx_code,
            'receive_date' => createTimestamp($request->receive_date, $request->receive_time),
            'is_export' => $request->is_export,
            'description' => $request->description,
            'create_by' => auth()->id(),
            'nopol' => $request->nopol,
            'driver' => $request->driver,
            'ttd' => $ttd,
            'phone_number' => $request->phone_number,
            'status' => $request->status,
            'package' => $request->input('package', null)
        ]);
        // Validasi handling area
        $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $request->warehouse_id);
        $handling_is_exists = $handling_storage->count();
        if ($handling_is_exists == 0) {
            $r = Rack::create([
                'warehouse_id' => $request->warehouse_id,
                'barcode' => '-',
                'code' => 'Handling Area',
                'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                'capacity_volume' => 100000,
                'capacity_tonase' => 100000
            ]);

            $rack_id = $r->id;
        } else {
            $r = $handling_storage->selectRaw('racks.id AS id')->first();
            $rack_id = $r->id;
        }


        $piece = Piece::where('name', 'Item')->first();
        $piece_id = $piece->id;
        foreach ($detail as $value) {
            if (empty($value)) {
                continue;
            }

            if(($value->item_id ?? null) != null) {
                $item = DB::table('items')
                ->whereId($value->item_id)
                ->first();
                if($item == null) {
                    return Response::json(['message' => 'ID Barang [' . $value->item_id . '] tidak ditemukan'], 422);
                }
            }            

            if(($value->imposition ?? null) != null && ($value->imposition ?? null) != 1  && ($value->imposition ?? null) != 2  && ($value->imposition ?? null) != 3  && ($value->imposition ?? null) != 4 ) {
                    return Response::json(['message' => 'Pengenaan [' . $value->imposition . '] tidak ditemukan'], 422);
            }

            if(($value->storage_type ?? null) == null) {
                    return Response::json(['message' => 'Tipe penyimpanan tidak boleh kosong. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
            } else {
                if(strtoupper($value->storage_type) != 'RACK' && strtoupper($value->storage_type) != 'HANDLING') {
                    return Response::json(['message' => 'Tipe penyimpanan [' . $value->storage_type . '] tidak dikenali. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                } else {
                    if(strtoupper($value->storage_type) == 'RACK') {
                        if(($value->rack_id ?? null) == null) {
                            return Response::json(['message' => 'ID Rak tidak boleh kosong jika tipe penyimpanan adalah rak. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                        } else {
                            $rack = DB::table('racks')
                            ->whereId($value->rack_id)
                            ->first();

                            if($rack == null) {
                                return Response::json(['message' => 'ID Rak [' . $value->rack_id . '] tidak ditemukan. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                            } else {
                                if($rack->warehouse_id != $request->warehouse_id) {
                                    $warehouse = DB::table('warehouses')
                                    ->whereId($request->warehouse_id)
                                    ->first();

                                    return Response::json(['message' => 'Rak ' . $rack->code . ' tidak ditemukan pada gudang ' . $warehouse->name . '. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                                }
                            }

                        }      
                    }
                }
            }

            if(($value->pallet_id ?? null) != null) {
                $item = DB::table('items')
                ->leftJoin('categories', 'categories.id', 'items.category_id')
                ->where('items.id', $value->pallet_id)
                ->select('categories.is_pallet')
                ->first();
                if($item == null) {
                    return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                } else {
                    if($item->is_pallet == 0) {
                        return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                    }
                }
            }            

            WarehouseReceiptDetail::create([
                'header_id' => $i->id,
                'storage_type' => $value->storage_type,
                'rack_id' => $value->storage_type == 'RACK' ? $value->rack_id : $rack_id,
                'piece_id' => $piece_id,
                'vehicle_type_id' => $value->vehicle_type_id ?? null,
                'qty' => $value->qty ?? 0,
                'weight' => $value->weight ?? 0,
                'volume' => ($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0),
                'long' => $value->long ?? 0,
                'wide' => $value->wide ?? 0,
                'high' => $value->high ?? 0,
                'imposition' => $value->imposition ?? 1,
                'item_id' => $value->item_id ?? null,
                'item_name' => $value->item_name ?? null,

                'is_use_pallet' => isset($value->is_use_pallet) ? $value->is_use_pallet : 0,
                'pallet_id' => isset($value->pallet_id) ? $value->pallet_id : null,
                'pallet_qty' => isset($value->pallet_id) ? ($value->pallet_qty ?? 0) : 0,
                'nopol' => $request->nopol,
                'driver_name' => $request->driver,
                'phone_number' => $request->phone_number,
                'leftover_warehouse' => $value->qty ?? 0,
                'leftover_stuffing' => $value->qty ?? 0,
                'weight_per_kg' => ($value->weight ?? 1 / $value->qty ?? 0),
                'volume_per_meter' => (($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0) / $value->qty ?? 0),
                'create_by' => auth()->id()
            ]);

            if ($request->status == 1 && isset($value->item_id)) {
                Item::find($value->item_id)->update([
                    'wide' => $value->wide,
                    'long' => $value->long,
                    'height' => $value->high,
                    'volume' => $value->wide * $value->long * $value->high,
                    'tonase' => $value->weight,
                ]);
            }
        }

        // Simpan lampiran
        foreach ($attachment as $unit) {
            DeliveryOrderPhoto::create([
                'receipt_id' => $i->id,
                'name' => $unit
            ]);
        }

        WarehouseReceiptDetail::update_item();
        DB::commit();
       
        } catch(Exception $e) {
            dd($e);
          return Response::json(['status' => 'ERROR', 'message' => $e->getMessage()], 422);
        }
        return Response::json(['message' => 'Transaksi penerimaan barang berhasil di-input'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!isset($request->company_id)) {
            return Response::json(["message" => "Cabang tidak boleh kosong"], 422);
        }
        if (!isset($request->customer_id)) {
            return Response::json(["message" => "Customer tidak boleh kosong"], 422);
        }
        if (!isset($request->warehouse_id)) {
            return Response::json(["message" => "Gudang tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_date)) {
            return Response::json(["message" => "Tanggal terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_time)) {
            return Response::json(["message" => "Waktu terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receiver)) {
            return Response::json(["message" => "Consignee tidak boleh kosong"], 422);
        }

        try {

        if (is_string($request->detail)) {

            $detail = json_decode($request->detail);
        } else {
            $detail = $request->detail;
        }

        if (!isset($detail)) {
            return Response::json(['message' => 'Item penerimaan barang tidak boleh kosong'], 422);
        }

        if(is_array($detail)) {
            foreach ($detail as $x => $value) {
                if (empty($value)) {
                    continue;
                }

                $detail[$x] = (object) $value;
            }
        }


        if ($request->status == 1) {
            if (!$request->has('files')) {
                return Response::json(['message' => 'Lampiran tidak boleh kosong'], 422);
            }
            $option = [
                'warehouse_id' => $request->warehouse_id
            ];
            $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($detail, $option);
            $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($detail, $option);
            if ($cek_kapasitas !== true) {
                return $cek_kapasitas;
            } else if ($cek_pallet_keluar !== true) {
                return $cek_pallet_keluar;
            }
        }

        DB::beginTransaction();
        $code = new TransactionCode($request->company_id, 'warehouseReceipt');
        $code->setCode();
        $trx_code = $code->getCode();
        $file = $request->file('files');
        $attachment = [];
        $c = 0;
        if(is_array($file)) {
            foreach ($file as $image) {
                $origin = $image->getClientOriginalName();
                $filename = 'LAMPIRAN_PENERIMAAN_BARANG' . date('Ymd_His') . $c . $origin;
                array_push($attachment, $filename);
                $img = Image::make($image->getRealPath());
                $img->resize(600, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path('files/' . $filename));
                $c++;
                
            }
        }

        if ($request->has('ttd')) {
            $ttd_file = $request->input('ttd');
            if (preg_match('/^(data\:image\/([a-zA-Z0-9]+);base64,)[a-zA-Z0-9=\/+\s]+$/', $ttd_file) == false) {
                return Response::json(['message' => 'Tanda tangan harus berformat base64'], 422);
            }
            $ttd_file_content = file_get_contents($ttd_file);
            $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.png';
            $img = Image::make(file_get_contents($ttd_file))->save(
                public_path('files') . '/' . $ttd
            );
        } else {
            $ttd = null;
        }

        $receiptType = DB::table('receipt_types')
        ->whereCode('ro4')
        ->first();

        $receipt_type_id = $receiptType->id ?? null;

        $i = WarehouseReceipt::create([
            'receipt_type_id' => $receipt_type_id,
            'company_id' => $request->company_id,
            'customer_id' => $request->customer_id,
            'receipt_type_id' => $request->receipt_type_id,
            'purchase_order_id' => $request->purchase_order_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'warehouse_id' => $request->warehouse_id,
            'city_to' => $request->city_to,
            'sender' => $request->sender,
            'receiver' => $request->receiver,
            'warehouse_staff_id' => auth()->id(),
            'reff_no' => $request->reff_no,
            'code' => $trx_code,
            'receive_date' => createTimestamp($request->receive_date, $request->receive_time),
            'is_export' => $request->is_export,
            'description' => $request->description,
            'create_by' => auth()->id(),
            'nopol' => $request->nopol,
            'driver' => $request->driver,
            'ttd' => $ttd,
            'phone_number' => $request->phone_number,
            'status' => $request->status,
            'package' => $request->input('package', null)
        ]);
        // Validasi handling area
        $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $request->warehouse_id);
        $handling_is_exists = $handling_storage->count();
        if ($handling_is_exists == 0) {
            $r = Rack::create([
                'warehouse_id' => $request->warehouse_id,
                'barcode' => '-',
                'code' => 'Handling Area',
                'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                'capacity_volume' => 100000,
                'capacity_tonase' => 100000
            ]);

            $rack_id = $r->id;
        } else {
            $r = $handling_storage->selectRaw('racks.id AS id')->first();
            $rack_id = $r->id;
        }


        $piece = Piece::where('name', 'Item')->first();
        $piece_id = $piece->id;

        if(is_array($detail)) {
            foreach ($detail as $value) {
                if (empty($value)) {
                    continue;
                }

                if(($value->item_id ?? null) != null) {
                    $item = DB::table('items')
                    ->whereId($value->item_id)
                    ->first();
                    if($item == null) {
                        return Response::json(['message' => 'ID Barang [' . $value->item_id . '] tidak ditemukan'], 422);
                    }
                }            

                if(($value->imposition ?? null) != null && ($value->imposition ?? null) != 1  && ($value->imposition ?? null) != 2  && ($value->imposition ?? null) != 3  && ($value->imposition ?? null) != 4 ) {
                        return Response::json(['message' => 'Pengenaan [' . $value->imposition . '] tidak ditemukan'], 422);
                }

                if(($value->storage_type ?? null) == null) {
                        return Response::json(['message' => 'Tipe penyimpanan tidak boleh kosong. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                } else {
                    if(strtoupper($value->storage_type) != 'RACK' && strtoupper($value->storage_type) != 'HANDLING') {
                        return Response::json(['message' => 'Tipe penyimpanan [' . $value->storage_type . '] tidak dikenali. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                    } else {
                        if(strtoupper($value->storage_type) == 'RACK') {
                            if(($value->rack_id ?? null) == null) {
                                return Response::json(['message' => 'ID Rak tidak boleh kosong jika tipe penyimpanan adalah rak. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                            } else {
                                $rack = DB::table('racks')
                                ->whereId($value->rack_id)
                                ->first();

                                if($rack == null) {
                                    return Response::json(['message' => 'ID Rak [' . $value->rack_id . '] tidak ditemukan. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                                } else {
                                    if($rack->warehouse_id != $request->warehouse_id) {
                                        $warehouse = DB::table('warehouses')
                                        ->whereId($request->warehouse_id)
                                        ->first();

                                        return Response::json(['message' => 'Rak ' . $rack->code . ' tidak ditemukan pada gudang ' . $warehouse->name . '. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                                    }
                                }

                            }      
                        }
                    }
                }

                if(($value->pallet_id ?? null) != null) {
                    $item = DB::table('items')
                    ->leftJoin('categories', 'categories.id', 'items.category_id')
                    ->where('items.id', $value->pallet_id)
                    ->select('categories.is_pallet')
                    ->first();
                    if($item == null) {
                        return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                    } else {
                        if($item->is_pallet == 0) {
                            return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                        }
                    }
                }            

                WarehouseReceiptDetail::create([
                    'header_id' => $i->id,
                    'storage_type' => $value->storage_type,
                    'rack_id' => $value->storage_type == 'RACK' ? $value->rack_id : $rack_id,
                    'piece_id' => $piece_id,
                    'vehicle_type_id' => $value->vehicle_type_id ?? null,
                    'qty' => $value->qty ?? 0,
                    'weight' => $value->weight ?? 0,
                    'kemasan' => $value->kemasan ?? null,
                    'volume' => ($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0),
                    'long' => $value->long ?? 0,
                    'wide' => $value->wide ?? 0,
                    'high' => $value->high ?? 0,
                    'imposition' => $value->imposition ?? 1,
                    'item_id' => $value->item_id ?? null,
                    'item_name' => $value->item_name ?? null,

                    'is_use_pallet' => isset($value->is_use_pallet) ? $value->is_use_pallet : 0,
                    'pallet_id' => isset($value->pallet_id) ? $value->pallet_id : null,
                    'pallet_qty' => isset($value->pallet_id) ? ($value->pallet_qty ?? 0) : 0,
                    'nopol' => $request->nopol,
                    'driver_name' => $request->driver,
                    'phone_number' => $request->phone_number,
                    'leftover_warehouse' => $value->qty ?? 0,
                    'leftover_stuffing' => $value->qty ?? 0,
                    'weight_per_kg' => ($value->weight ?? 1 / $value->qty ?? 0),
                    'volume_per_meter' => (($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0) / $value->qty ?? 0),
                    'create_by' => auth()->id()
                ]);

                if ($request->status == 1 && isset($value->item_id)) {
                    Item::find($value->item_id)->update([
                        'wide' => $value->wide,
                        'long' => $value->long,
                        'height' => $value->high,
                        'volume' => $value->wide * $value->long * $value->high,
                        'tonase' => $value->weight,
                    ]);
                }
            }
        }

        // Simpan lampiran
        if(is_array($attachment)) {
            foreach ($attachment as $unit) {
                DeliveryOrderPhoto::create([
                    'receipt_id' => $i->id,
                    'name' => $unit
                ]);
            }
        }

        WarehouseReceiptDetail::update_item();
        DB::commit();
       
        } catch(Exception $e) {
          return Response::json(['status' => 'ERROR', 'message' => $e->getMessage()], 422);
        }
        return Response::json(['message' => 'Transaksi penerimaan barang berhasil di-input'], 200);
    }

    /*
      Date : 14-04-2020
      Description : Store Penerimaan Barang with files
      Developer : Dimas
      Status : Create
    */
    /*
      Date : 15-04-2020
      Description : Store Penerimaan Barang with files
      Developer : Didin
      Status : Edit
    */
    public function store_alternative(Request $request)
    {
        // $request->validate([
        //   'company_id' => 'required',
        //   'customer_id' => 'required',
        //   'warehouse_id' => 'required',
        //   'receiver' => 'required',
        //   'receive_date' => 'required',
        //   'receive_time' => 'required'
        // ]);
        if (!isset($request->company_id)) {
            return Response::json(["message" => "Cabang tidak boleh kosong"], 422);
        }
        if (!isset($request->customer_id)) {
            return Response::json(["message" => "Customer tidak boleh kosong"], 422);
        }
        if (!isset($request->warehouse_id)) {
            return Response::json(["message" => "Gudang tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_date)) {
            return Response::json(["message" => "Tanggal terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_time)) {
            return Response::json(["message" => "Waktu terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receiver)) {
            return Response::json(["message" => "Consignee tidak boleh kosong"], 422);
        }


        if (is_string($request->detail)) {

            $detail = json_decode($request->detail);
        } else {
            $detail = $request->detail;
        }

        if (!isset($detail)) {
            return Response::json(['message' => 'Item penerimaan barang tidak boleh kosong'], 422);
        }
        //      if(!$request->has('files')) {
        //      	return Response::json(['message' => 'Lampiran tidak boleh kosong'], 422);
        //      }

        foreach ($detail as $x => $value) {
            if (empty($value)) {
                continue;
            }

            $detail[$x] = (object) $value;
            $value = (object) $value;
            if(($value->item_id ?? null) != null) {
                $item = DB::table('items')
                ->whereId($value->item_id)
                ->first();
                if($item == null) {
                    return Response::json(['message' => 'ID Barang [' . $value->item_id . '] tidak ditemukan'], 422);
                }
            }            
            if(($value->imposition ?? null) != null && ($value->imposition ?? null) != 1  && ($value->imposition ?? null) != 2  && ($value->imposition ?? null) != 3  && ($value->imposition ?? null) != 4 ) {
                    return Response::json(['message' => 'Pengenaan [' . $value->imposition . '] tidak ditemukan'], 422);
            }

            if(($value->storage_type ?? null) == null) {
                    return Response::json(['message' => 'Tipe penyimpanan tidak boleh kosong. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
            } else {
                if(strtoupper($value->storage_type) != 'RACK' && strtoupper($value->storage_type) != 'HANDLING') {
                    return Response::json(['message' => 'Tipe penyimpanan [' . $value->storage_type . '] tidak dikenali. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                } else {
                    if(strtoupper($value->storage_type) == 'RACK') {
                        if(($value->rack_id ?? null) == null) {
                            return Response::json(['message' => 'ID Rak tidak boleh kosong jika tipe penyimpanan adalah rak. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                        } else {
                            $rack = DB::table('racks')
                            ->whereId($value->rack_id)
                            ->first();

                            if($rack == null) {
                                return Response::json(['message' => 'ID Rak [' . $value->rack_id . '] tidak ditemukan. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                            } else {
                                if($rack->warehouse_id != $request->warehouse_id) {
                                    $warehouse = DB::table('warehouses')
                                    ->whereId($request->warehouse_id)
                                    ->first();

                                    return Response::json(['message' => 'Rak ' . $rack->code . ' tidak ditemukan pada gudang ' . $warehouse->name . '. ID Barang [' . ($value->item_id ?? '') . ']'], 422);
                                }
                            }

                        }      
                    }
                }
            }

            if(($value->pallet_id ?? null) != null) {
                $item = DB::table('items')
                ->leftJoin('categories', 'categories.id', 'items.category_id')
                ->where('items.id', $value->pallet_id)
                ->select('categories.is_pallet')
                ->first();
                if($item == null) {
                    return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                } else {
                    if($item->is_pallet == 0) {
                        return Response::json(['message' => 'ID Pallet [' . $value->pallet_id . '] tidak ditemukan'], 422);
                    }
                }
            }
        }

        if (!$request->has('files')) {
            return Response::json(['message' => 'Lampiran tidak boleh kosong'], 422);
        }

        if ($request->status == 1) {
            $option = [
                'warehouse_id' => $request->warehouse_id
            ];
            $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($detail, $option);
            $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($detail, $option);
            if ($cek_kapasitas !== true) {
                return $cek_kapasitas;
            } else if ($cek_pallet_keluar !== true) {
                return $cek_pallet_keluar;
            }
        }

        DB::beginTransaction();
        $code = new TransactionCode($request->company_id, 'warehouseReceipt');
        $code->setCode();
        $trx_code = $code->getCode();
        $file = $request->file('files');
        $attachment = [];
        $c = 0;
        foreach ($file as $image) {
            $origin = $image->getClientOriginalName();
            $filename = 'LAMPIRAN_PENERIMAAN_BARANG' . date('Ymd_His') . $c . $origin;
            array_push($attachment, $filename);
            $img = Image::make($image->getRealPath());
            $img->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path('files/' . $filename));
            $c++;
            
        }

        if ($request->has('ttd')) {
            $ttd_file = $request->input('ttd');
            if (preg_match('/^(data\:image\/([a-zA-Z0-9]+);base64,)[a-zA-Z0-9=\/+\s]+$/', $ttd_file) == false) {
                return Response::json(['message' => 'Tanda tangan harus berformat base64'], 422);
            }
            $ttd_file_content = file_get_contents($ttd_file);
            $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.png';
            $img = Image::make(file_get_contents($ttd_file))->save(
                public_path('files') . '/' . $ttd
            );
        } else {
            $ttd = null;
        }

        $i = WarehouseReceipt::create([
            'company_id' => $request->company_id,
            'customer_id' => $request->customer_id,
            'warehouse_id' => $request->warehouse_id,
            'city_to' => $request->city_to,
            'sender' => $request->sender,
            'receiver' => $request->receiver,
            'warehouse_staff_id' => auth()->id(),
            // 'collectible_id' => $request->collectible_id,
            'reff_no' => $request->reff_no,
            'code' => $trx_code,
            'receive_date' => createTimestamp($request->receive_date, $request->receive_time),
            'is_export' => $request->is_export,
            'description' => $request->description,
            'create_by' => auth()->id(),
            'nopol' => $request->nopol,
            'driver' => $request->driver,
            'ttd' => $ttd,
            'phone_number' => $request->phone_number,
            'status' => $request->status,
            'package' => $request->input('package', null)
        ]);
        // Validasi handling area
        $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $request->warehouse_id);
        $handling_is_exists = $handling_storage->count();
        if ($handling_is_exists == 0) {
            $r = Rack::create([
                'warehouse_id' => $request->warehouse_id,
                'barcode' => '-',
                'code' => 'Handling Area',
                'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                'capacity_volume' => 100000,
                'capacity_tonase' => 100000
            ]);

            $rack_id = $r->id;
        } else {
            $r = $handling_storage->selectRaw('racks.id AS id')->first();
            $rack_id = $r->id;
        }


        $piece = Piece::where('name', 'Item')->first();
        $piece_id = $piece->id;
        foreach ($detail as $value) {
            if (empty($value)) {
                continue;
            }            

            WarehouseReceiptDetail::create([
                'header_id' => $i->id,
                'storage_type' => $value->storage_type,
                'rack_id' => $value->storage_type == 'RACK' ? $value->rack_id : $rack_id,
                'piece_id' => $piece_id,
                'vehicle_type_id' => $value->vehicle_type_id ?? null,
                'qty' => $value->qty ?? 0,
                'weight' => $value->weight ?? 0,
                'volume' => ($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0),
                'long' => $value->long ?? 0,
                'wide' => $value->wide ?? 0,
                'high' => $value->high ?? 0,
                'imposition' => $value->imposition ?? 1,
                'item_id' => $value->item_id ?? null,
                'item_name' => $value->item_name ?? null,

                'is_use_pallet' => isset($value->is_use_pallet) ? $value->is_use_pallet : 0,
                'pallet_id' => isset($value->pallet_id) ? $value->pallet_id : null,
                'pallet_qty' => isset($value->pallet_id) ? ($value->pallet_qty ?? 0) : 0,
                'nopol' => $request->nopol,
                'driver_name' => $request->driver,
                'phone_number' => $request->phone_number,
                'leftover_warehouse' => $value->qty ?? 0,
                'leftover_stuffing' => $value->qty ?? 0,
                'weight_per_kg' => ($value->weight ?? 1 / $value->qty ?? 0),
                'volume_per_meter' => (($value->long ?? 0 * $value->wide ?? 0 * $value->high ?? 0) / $value->qty ?? 0),
                'create_by' => auth()->id()
            ]);

            if ($request->status == 1 && isset($value->item_id)) {
                Item::find($value->item_id)->update([
                    'wide' => $value->wide,
                    'long' => $value->long,
                    'height' => $value->high,
                    'volume' => $value->wide * $value->long * $value->high,
                    'tonase' => $value->weight,
                ]);
            }
        }

        // Simpan lampiran
        foreach ($attachment as $unit) {
            DeliveryOrderPhoto::create([
                'receipt_id' => $i->id,
                'name' => $unit
            ]);
        }

        WarehouseReceiptDetail::update_item();
        DB::commit();

        return Response::json(['message' => 'Transaksi penerimaan barang berhasil di-input'], 200);
    }
    
    /*
      Date : 14-04-2020
      Description : Menghapus detail penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function delete_detail($id)
    {
        DB::beginTransaction();
        if (WarehouseReceiptDetail::find($id) == null) {
            return Response::json(['message' => 'Detail penerimaan barang tidak ditemukan'], 404);
            }
            $wd = WarehouseReceiptDetail::find($id);
            if($wd->header->status == 1) {
                
              $job_order_details = DB::table('job_order_details')
              ->whereWarehouseReceiptDetailId($id)
              ->count();

              if($job_order_details > 0) {
                  return Response::json(['message' => 'Barang ini tidak dapat dihapus, karena sudah mempunyai job order'], 422);
              }

              $item_migration_details = DB::table('item_migration_details')
              ->whereWarehouseReceiptDetailId($id)
              ->count();

              if($item_migration_details > 0) {
                  return Response::json(['message' => 'Barang ini tidak dapat dihapus, karena sudah mempunyai migrasi'], 422);
              }

              $stock_transactions = DB::table('stock_transactions')
              ->whereWarehouseReceiptDetailId($id)
              ->get();

              foreach ($stock_transactions as $stock) {
                  DB::table('stock_transactions_report')
                  ->whereHeaderId($stock->id)
                  ->delete();

                  DB::table('warehouse_stock_details') 
                  ->whereWarehouseReceiptId($wd->header->id)
                  ->whereItemId($stock->item_id)
                  ->whereRackId($stock->rack_id)
                  ->decrement('qty', $stock->qty_masuk - $stock->qty_keluar);

                  DB::table('warehouse_stocks') 
                  ->whereItemId($stock->item_id)
                  ->whereWarehouseId($stock->warehouse_id)
                  ->decrement('qty', $stock->qty_masuk - $stock->qty_keluar);

                  $volume = ($stock->qty_masuk - $stock->qty_keluar) * $wd->long * $wd->wide * $wd->high / 1000000;
                  DB::table('racks')
                  ->whereId($wd->rack_id)
                  ->decrement('capacity_volume_used', $volume);

                  $tonase = ($stock->qty_masuk - $stock->qty_keluar) * $wd->weight;                
                  DB::table('racks')
                  ->whereId($wd->rack_id)
                  ->decrement('capacity_tonase_used', $tonase);


              }

              $stock_transactions = DB::table('stock_transactions')
              ->whereWarehouseReceiptDetailId($id)
              ->delete();
        }
        $wd->delete();
        DB::commit();

        return Response::json(['message' => 'Item transaksi penerimaan barang berhasil dihapus'], 422);
    }

    /*
      Date : 14-04-2020
      Description : Mengedit detail penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function update_detail(Request $request, $id)
    {
        if (!isset($request->storage_type)) {
            return Response::json(["message" => "Tipe penyimpanan tidak boleh kosong"], 422);
        }
        if (!isset($request->imposition)) {
            return Response::json(["message" => "Pengenaan tidak boleh kosong"], 422);
        }
        

        if(!$request->filled('item_id') && !$request->filled('item_name')) {
            return Response::json(["message" => "ID Barang dan nama barang tidak boleh kosong"], 422);
        }

        DB::beginTransaction();
        $w = WarehouseReceiptDetail::find($id);
        if ($w == null) {
            return Response::json(['message' => 'Detail penerimaan barang tidak ditemukan'], 422);
        }

        if($w->header->status == 1) {
            
          
          $job_order_details = DB::table('job_order_details')
          ->whereWarehouseReceiptDetailId($id)
          ->count();

          if($job_order_details > 0) {
              return Response::json(['message' => 'Barang ini tidak dapat di-update, karena sudah mempunyai job order'], 422);
          }

          $item_migration_details = DB::table('item_migration_details')
          ->whereWarehouseReceiptDetailId($id)
          ->count();

          if($item_migration_details > 0) {
              return Response::json(['message' => 'Barang ini tidak dapat di-update, karena sudah mempunyai migrasi'], 422);
          }

          $stock_transactions = DB::table('stock_transactions')
          ->whereWarehouseReceiptDetailId($id)
          ->get();

          $qty_origin = $w->qty;
          $offset = $qty_origin - ($request->qty ?? 0);

          foreach ($stock_transactions as $stock) {
              DB::table('stock_transactions_report')
              ->whereHeaderId($stock->id)
              ->decrement('qty_masuk', $offset);
              DB::table('stock_transactions_report')
              ->whereHeaderId($stock->id)
              ->update([
                 'rack_id' => $request->rack_id,
              ]);

              DB::table('warehouse_stock_details') 
              ->whereWarehouseReceiptId($w->header->id)
              ->whereItemId($stock->item_id)
              ->whereRackId($stock->rack_id)
              ->decrement('qty', $offset);
              DB::table('warehouse_stock_details') 
              ->whereWarehouseReceiptId($w->header->id)
              ->whereItemId($stock->item_id)
              ->whereRackId($stock->rack_id)
              ->update([
                 'rack_id' => $request->rack_id,
              ]);

              DB::table('warehouse_stocks') 
              ->whereItemId($stock->item_id)
              ->whereWarehouseId($stock->warehouse_id)
              ->decrement('qty', $offset);

              $volume = $w->qty * $w->long * $w->wide * $w->high / 1000000;
              DB::table('racks')
              ->whereId($w->rack_id)
              ->decrement('capacity_volume_used', $volume);

              $tonase = $w->qty * $w->weight;                
              DB::table('racks')
              ->whereId($w->rack_id)
              ->decrement('capacity_tonase_used', $tonase);

              $volume = $request->qty * $request->long * $request->wide * $request->high / 1000000;
              DB::table('racks')
              ->whereId($request->rack_id)
              ->increment('capacity_volume_used', $volume);

              $tonase = $request->qty * $request->weight;                
              DB::table('racks')
              ->whereId($request->rack_id)
              ->increment('capacity_tonase_used', $tonase);
          }

          $option = [
            'warehouse_id' => $w->header->warehouse_id
          ];
          $params = [
              (object)[
                  'rack_id' => $request->rack_id,
                  'weight' => $request->weight,
                  'qty' => $request->qty,
                  'long' => $request->long,
                  'wide' => $request->wide,
                  'high' => $request->high,
                  'storage_type' => $request->storage_type,
              ]
          ];

          $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($params, $option);
          if($cek_kapasitas !== true) {
              return $cek_kapasitas;
          }

          $stock_transactions = DB::table('stock_transactions')
          ->whereWarehouseReceiptDetailId($id)
          ->decrement('qty_masuk', $offset);
        }
        $piece = Piece::where('name', 'Item')->first();
        $piece_id = $piece->id;
        $detail = WarehouseReceiptDetail::find($id);
        $header = WarehouseReceipt::find($detail->header_id);
        
        if ($request->storage_type == 'HANDLING') {
            $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $header->warehouse_id);
            $handling_is_exists = $handling_storage->count();
            if ($handling_is_exists == 0) {
                $r = Rack::create([
                    'warehouse_id' => $header->warehouse_id,
                    'barcode' => '-',
                    'code' => 'Handling Area',
                    'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                    'capacity_volume' => 1000000,
                    'capacity_tonase' => 1000000
                ]);

                $rack_id = $r->id;
            } else {
                $r = $handling_storage->selectRaw('racks.id AS id')->first();
                $rack_id = $r->id;
            }
        } else {
            if(!$request->filled('rack_id')) {
                return Response::json(["message" => "Rak penyimpanan tidak boleh kosong jika tipe penyimpanan adalah rak"], 422);
            }
            $rack = DB::table('racks')
            ->whereId($request->rack_id)
            ->first();

            if($rack == null) {
                return Response::json(["message" => "Rak penyimpanan tidak ditemukan"], 422);
            } else {
                if($rack->warehouse_id != $header->warehouse_id) {
                    $warehouse = DB::table('warehouses')
                    ->whereId($header->warehouse_id)
                    ->first();

                    return Response::json(['message' => 'Rak ' . $rack->code . ' tidak ditemukan di Gudang ' . $warehouse->name], 422);
                }
            }
            $rack_id = $request->rack_id;
        }

        $detail->update([
            'storage_type' => $request->storage_type,
            'rack_id' => $rack_id,
            'piece_id' => $piece_id,
            'vehicle_type_id' => $request->vehicle_type_id ?? null,
            'qty' => $request->qty ?? 0,
            'weight' => $request->weight ?? 0,
            'volume' => ($request->long ?? 0 * $request->wide ?? 0 * $request->high ?? 0),
            'long' => $request->long ?? 0,
            'wide' => $request->wide ?? 0,
            'high' => $request->high ?? 0,
            'imposition' => $request->imposition ?? 1,
            'is_exists' => $request->is_exists,
            'item_id' => $request->is_exists == 0 ? DB::raw("item_id") : $request->item_id,
            'item_name' => $request->item_name ?? null,
            'pallet_id' => $request->pallet_id,
            'pallet_qty' => $request->pallet_qty ?? 0,
            'create_by' => auth()->id()
        ]);
        WarehouseReceiptDetail::update_item();
        DB::commit();

        return Response::json(['message' => 'Item transaksi penerimaan barang berhasil di-update'], 200);
    }

    /*
      Date : 14-04-2020
      Description : Menambah detail penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function store_detail(Request $request, $id)
    {
        DB::beginTransaction();
        $w = WarehouseReceipt::find($id);
        if ($w == null) {
            return Response::json(['message' => 'Transaksi penerimaan barang tidak ditemukan'], 422);
        }
        


        if (!isset($request->storage_type)) {
            return Response::json(["message" => "Tipe penyimpanan tidak boleh kosong"], 422);
        }
        if (!isset($request->imposition)) {
            return Response::json(["message" => "Pengenaan tidak boleh kosong"], 422);
        }
        

        if(!$request->filled('item_id') && !$request->filled('item_name')) {
            return Response::json(["message" => "ID Barang dan nama barang tidak boleh kosong"], 422);
        }
        $piece = Piece::where('name', 'Item')->first();
        $piece_id = $piece->id;
        if ($request->storage_type == 'HANDLING') {
            // Validasi handling area
            $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $request->warehouse_id);
            $handling_is_exists = $handling_storage->count();
            if ($handling_is_exists == 0) {
                $r = Rack::create([
                    'warehouse_id' => $w->warehouse_id,
                    'barcode' => '-',
                    'code' => 'Handling Area',
                    'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                    'capacity_volume' => 100000,
                    'capacity_tonase' => 100000
                ]);

                $rack_id = $r->id;
            } else {
                $r = $handling_storage->selectRaw('racks.id AS id')->first();
                $rack_id = $r->id;
            }
        } else {
            if(!$request->filled('rack_id')) {
                return Response::json(["message" => "Rak penyimpanan tidak boleh kosong jika tipe penyimpanan adalah rak"], 422);
            }
            $rack = DB::table('racks')
            ->whereId($request->rack_id)
            ->first();

            if($rack == null) {
                return Response::json(["message" => "Rak penyimpanan tidak ditemukan"], 422);
            } else {
                if($rack->warehouse_id != $w->warehouse_id) {
                    $warehouse = DB::table('warehouses')
                    ->whereId($w->warehouse_id)
                    ->first();

                    return Response::json(['message' => 'Rak ' . $rack->code . ' tidak ditemukan di Gudang ' . $warehouse->name], 422);
                }
            }
            $rack_id = $request->rack_id;
        }

        WarehouseReceiptDetail::create([
            'header_id' => $id,
            'storage_type' => $request->storage_type,
            'rack_id' => $rack_id,
            'piece_id' => $piece_id,
            'vehicle_type_id' => $request->vehicle_type_id ?? null,
            'qty' => $request->qty ?? 0,
            'weight' => $request->weight ?? 0,
            'volume' => ($request->long ?? 0 * $request->wide ?? 0 * $request->high ?? 0),
            'long' => $request->long ?? 0,
            'wide' => $request->wide ?? 0,
            'high' => $request->high ?? 0,
            'imposition' => $request->imposition ?? 1,
            'item_id' => $request->item_id ?? null,
            'item_name' => $request->item_name ?? null,
            'pallet_id' => $request->pallet_id ?? null,
            'pallet_qty' => $request->pallet_qty ?? 0,

            'create_by' => auth()->id()
        ]);

        WarehouseReceiptDetail::update_item();
        DB::commit();

        return Response::json(['message' => 'Item transaksi penerimaan barang berhasil di-input'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(WarehouseReceipt::find($id) == null) {
            return Response::json(['message' => 'ID Tidak Ditemukan'], 404);
        }
        $data = [];
        $data['item'] = WarehouseReceipt::with('customer:id,name', 'warehouse:id,name', 'warehouse_staff:id,name')
        ->where('warehouse_receipts.id', $id)
        ->leftJoin('vehicle_types', 'vehicle_types.id', 'warehouse_receipts.vehicle_type_id')
        ->selectRaw("warehouse_receipts.id, warehouse_receipts.code, warehouse_receipts.description, warehouse_receipts.status, IF(status = 0, 'Draft', 'Disetujui') AS status_name, warehouse_receipts.company_id, warehouse_receipts.customer_id, sender, receiver, receive_date, stripping_done, warehouse_staff_id, city_to, reff_no, warehouse_id, nopol, driver, phone_number, ttd, package, warehouse_receipts.vehicle_type_id, vehicle_types.name AS vehicle_type_name, CONCAT('PO0001') AS purchase_order_code")->first();

        if($data['item']->ttd != null) {
            $data['item']->ttd = asset('files') . '/' . $data['item']->ttd;
        }
        $warehouse_receipt_code = $data['item']->code;

        $verifieds = DB::raw('(SELECT warehouse_receipt_details.id AS warehouse_receipt_detail_id, ROUND(RAND()) AS verified_status_id FROM warehouse_receipt_details) AS verifieds');

        $data['detail'] = WarehouseReceiptDetail::with('rack:id,code,capacity_tonase,capacity_volume', 'pallet:id,name')
        ->join($verifieds, 'verifieds.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
        ->where('warehouse_receipt_details.header_id', $id)
        ->select('warehouse_receipt_details.id', 'warehouse_receipt_details.rack_id', 'storage_type', 'warehouse_receipt_details.item_id', 'item_name', 'imposition', DB::raw("IF(imposition = 1, 'Kubikasi', IF(imposition = 2, 'Tonase', IF(imposition = 3, 'Item', 'Borongan'))) AS imposition_name"), 'long', 'wide', 'high', 'weight', 'warehouse_receipt_details.qty', 'pallet_id', 'pallet_qty', DB::raw('IFNULL((SELECT sum(qty) FROM warehouse_stock_details WHERE item_id = warehouse_receipt_details.item_id AND no_surat_jalan = "' . $warehouse_receipt_code . '"), 0) AS stock'), DB::raw('IF(verifieds.verified_status_id = 1, "Verified", "Unverified") AS verified_status_name'), 'verifieds.verified_status_id')
        ->get();
        $path = asset('files') . '/';
        $surat_jalan = DeliveryOrderPhoto::where('receipt_id', $id)->selectRaw("CONCAT('$path', name) AS name")->get();
        $list_surat_jalan = [];
        foreach ($surat_jalan as $x) {
            # code...
            array_push($list_surat_jalan, $x->name);
        }
        $data['surat_jalan'] = $list_surat_jalan;

       
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    public function item($id)
    {
        WarehouseReceipt::findOrFail($id);
        $data = WarehouseReceipt::where('id', $id)->selectRaw("id, code, created_at")->first();
        $data['detail'] = DB::table('warehouse_receipt_details')
        ->join('items', 'item_id', 'items.id')
        ->whereHeaderId($id)
        ->select('items.code', 'items.name', 'warehouse_receipt_details.high', 'warehouse_receipt_details.wide', 'warehouse_receipt_details.long', 'warehouse_receipt_details.weight', 'warehouse_receipt_details.qty', 'warehouse_receipt_details.qty', DB::raw("IF(imposition = 1, 'Kubikasi', IF(imposition = 2, 'Tonase', IF(imposition = 3, 'Item', 'Borongan'))) AS imposition_name"))
        ->get();

       
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function print($id)
    {
        $w = WarehouseReceipt::find($id);
        if ($w == null) {

            return Response::json(['message' => 'Transaksi penerimaan barang tidak ditemukan'], 422);
        }
        $w->update([
            'stripping_done' => DB::raw("(SELECT NOW())"),
            'receive_date' => DB::raw('receive_date')
        ]);

        $data['item'] = WarehouseReceipt::with('customer.company', 'collectible', 'warehouse', 'staff')->where('id', $id)->first();
        $data['item']->prefix = preg_replace('/(.+\/)(\d+)$/', '$1', $data['item']->code);
        $data['item']->suffix = preg_replace('/(.+\/)(\d+)$/', '$2', $data['item']->code);
        $data['detail'] = WarehouseReceiptDetail::with('piece', 'vehicle_type', 'rack')->where('header_id', $id)->get();
        $data['surat_jalan'] = DeliveryOrderPhoto::where('receipt_id', $id)->get();
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
        if (WarehouseReceipt::where('id', $id)->first() == null) {
            return Response::json(['message' => 'Transaksi penerimaan barang tidak ditemukan'], 422);
        }
        $query = WarehouseReceipt::where('id', $id);
        $query->with(['Warehouse' => function ($query) {
            $query->select('id', 'name');
        }]);
        $query->with(['Company' => function ($query) {
            $query->select('id', 'name');
        }]);
        $data['item'] = $query->first(['id', 'company_id', 'warehouse_id', 'receive_date', 'stripping_done', 'customer_id', 'package', 'sender', 'receiver', 'description', 'city_to', 'reff_no', 'nopol', 'phone_number', 'ttd']);
        $data['item']->ttd = $data['item']->ttd ? asset('files') . '/' . $data['item']->ttd : null;
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 09-07-2020
      Description : Menambah lampiran pada penerimaan barang
      Developer : Didin
      Status : Edit
    */
    public function update(Request $request, $id)
    {
        if (!isset($request->customer_id)) {
            return Response::json(["message" => "Customer tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_date)) {
            return Response::json(["message" => "Tanggal terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receive_time)) {
            return Response::json(["message" => "Waktu terima tidak boleh kosong"], 422);
        }
        if (!isset($request->receiver)) {
            return Response::json(["message" => "Consignee tidak boleh kosong"], 422);
        }
        if (WarehouseReceipt::where('id', $id)->first() == null) {

            return Response::json(['message' => 'Transaksi penerimaan barang tidak ditemukan'], 422);
        }
        DB::beginTransaction();
        $i = WarehouseReceipt::find($id);
        $ttd_origin = $i->ttd;

        $inputs = [
            'customer_id' => $request->customer_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'city_to' => $request->city_to,
            'sender' => $request->sender,
            'receiver' => $request->receiver,
            'reff_no' => $request->reff_no,
            'receive_date' => createTimestamp($request->receive_date, $request->receive_time),
            'description' => $request->description,
            'create_by' => auth()->id(),
            'nopol' => $request->nopol,
            'driver' => $request->driver,
            'phone_number' => $request->phone_number,
            'package' => $request->input('package', null),
            'description' => $request->description
        ];
        if (isset($request->ttd)) {
            $ttd_file = $request->input('ttd');
            if (preg_match('/^(data\:image\/png;base64,)[a-zA-Z0-9=\/+\s]+$/', $ttd_file) == false) {
                return Response::json(['message' => 'Tanda tangan harus berformat base64'], 422);
            }
            $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.png';
            Image::make(file_get_contents($ttd_file))->save(public_path('files/' . $ttd));
            $inputs['ttd'] = $ttd;
            File::delete(public_path('files/' . $ttd_origin));
        }
        $i->update($inputs);
        DB::commit();

        return Response::json(['message' => 'Transaksi penerimaan barang berhasil di-update'], 200);
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

    public function approve($id)
    {
        DB::beginTransaction();
        $wd = WarehouseReceiptDetail::where('header_id', $id)->get();
        $w = WarehouseReceipt::find($id);
        if ($w == null) {
            return Response::json(['message' => 'Transaksi penerimaan barang tidak ditemukan'], 422);
        } else {
            if ($w->status == 1) {

                return Response::json(['message' => 'Transaksi penerimaan barang tidak dapat disetujui karena transaksi ini sudah pernah di-approve sebelumnya'], 422);
            }
        }
        $option = ['warehouse_id' => $w->warehouse_id];
        $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($wd, $option);
        $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($wd, $option);
        if ($cek_kapasitas !== true) {
            return $cek_kapasitas;
        } else if ($cek_pallet_keluar !== true) {
            return $cek_pallet_keluar;
        }

        $w->status = 1;
        $detail = WarehouseReceiptDetail::where("header_id", $id)->whereRaw('item_id IS NOT NULL')->get();

        // Meng-update dimensi dan berat di master item
        foreach ($detail as $value) {
            # code...
            Item::find($value->item_id)->update([
                'wide' => $value->wide,
                'long' => $value->long,
                'height' => $value->high,
                'volume' => $value->wide * $value->long * $value->high,
                'tonase' => $value->weight,
            ]);
        }
        $w->save();
        DB::commit();

        return Response::json(['message' => 'Transaksi penerimaan barang berhasil disetujui']);
    }

    /*
      Date : 16-04-2020
      Description : Scan barcode barang
      Developer : Didin
      Status : Create
    */
    public static function scanBarcode(Request $request) {
        $dt = WR::scanBarcode($request->barcode);
        $data['data'] = $dt;
        if(count($dt) > 0) {
            $data['message'] = 'OK';
            $data['success'] = true;
        } else {
            $data['message'] = 'Barang tidak ditemukan';
            $data['success'] = false;
        }

        return response()->json($data);
    }
}

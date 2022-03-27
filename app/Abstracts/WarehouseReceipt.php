<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\WarehouseReceipt AS WR;
use App\Abstracts\Setting\Email;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Inventory\WarehouseReceiptStatus;
use App\Abstracts\Inventory\ItemMigrationReceipt;
use App\Abstracts\Inventory\VoyageReceipt;
use App\Abstracts\PurchaseOrder;
use App\Model\WarehouseStockDetail;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\StockTransaction;
use Image;
use Illuminate\Support\Str;

class WarehouseReceipt
{
    protected static $table = 'warehouse_receipts';

    public static function setEmptyDescription() {
        $dt = DB::table(self::$table);
        $dt = $dt->where('description', 'null');
        $dt = $dt->orWhere('description', 'NULL');
        $dt = $dt->update([
            'description' => null
        ]);
    }

    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $dt = DB::table(self::$table);

        if($request['code']) {
            $dt->where(self::$table . '.code', $request['code']);
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['code'] = $args['code'] ?? null;

        return $params;
    }

    /*
      Date : 16-03-2021
      Description : Menampilkan foto
      Developer : Didin
      Status : Create
    */
    public static function showAttachment($receipt_id) {
        $url = asset('files');
        $attachment = DB::table('delivery_order_photos')
        ->whereReceiptId($receipt_id)
        ->selectRaw("id, CONCAT('$url/', `name`) AS `name`")
        ->get();

        return $attachment;
    }

    /*
      Date : 29-08-2020
      Description : Mengirim email
      Developer : Didin
      Status : Create
    */
    public static function sendEmail($id) {
        $emailSetting = Email::show();
        $dt = DB::table('warehouse_receipts')
        ->join('contacts', 'warehouse_receipts.customer_id', 'contacts.id')
        ->where('warehouse_receipts.id', $id)
        ->select('contacts.email', 'contacts.name AS customer_name')
        ->first();
        $email = $dt->email;
        $email = str_replace(',', ';', $email);
        $destination = $email;
        $destination_name = $dt->customer_name;
        $subject = $emailSetting->receipt_subject;

        $body = self::previewEmail($id);

        Email::send($subject, $destination, $destination_name, $body);

    }

    /*
      Date : 29-08-2020
      Description : Menampilkan preview email
      Developer : Didin
      Status : Create
    */
    public static function previewEmail($id) {
        $wr = DB::table('warehouse_receipts')
        ->whereId($id)
        ->first();

        $images = self::showAttachment($id)->pluck('name');

        $frame = '';
        foreach ($images as $image) {
            $frame .= "<a href='$image' style='display:inline-block;width:60mm;margin-right:50mm;margin-bottom:5mm'><img src='$image' style='height:60mm;width:auto' /></a>";
        }

        $email = Email::show();
        $warehouseReceipt = new \App\Http\Controllers\OperationalWarehouse\ReceiptController();
        $stocklist = $warehouseReceipt->print($id);
        $body = '<div>';
        $body .= $email->receipt_body;
        $body .= '<br>';
        $body .= '<hr>';
        $body .= $frame;
        $body .= '</div>';

        return $body;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['detail'] = $args['detail'] ?? null;
        $params['files'] = $args['files'] ?? null;
        $params['ttd'] = $args['ttd'] ?? null;
        $params['warehouse_staff_id'] = $args['warehouse_staff_id'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;
        $params['company_id'] = $args['company_id'] ?? null;
        $params['purchase_order_id'] = $args['purchase_order_id'] ?? null;
        $params['customer_id'] = $args['customer_id'] ?? null;
        $params['receipt_type_id'] = $args['receipt_type_id'] ?? null;
        if(!$params['receipt_type_id']) {
            $receipt_type_code = $args['receipt_type_code'] ?? null;
            if($receipt_type_code) {
                $rt = ReceiptType::showByCode($receipt_type_code);
                if($rt) {
                    $params['receipt_type_id'] = $rt->id;
                }
            }
        }

        $params['is_overtime'] = $args['is_overtime'] ?? null ?? 0;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['city_to'] = $args['city_to'] ?? null;
        $params['sender'] = $args['sender'] ?? null;
        $params['receiver'] = $args['receiver'] ?? null;
        $params['reff_no'] = $args['reff_no'] ?? null;
        $params['is_export'] = $args['is_export'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['nopol'] = $args['nopol'] ?? null;
        $params['driver'] = $args['driver'] ?? null;
        $params['phone_number'] = $args['phone_number'] ?? null;
        $params['vehicle_type_id'] = $args['vehicle_type_id'] ?? null;
        $params['is_pallet'] = $args['is_pallet'] ?? null;
        $params['status'] = $args['status'] ?? null;
        $params['receive_date'] = $args['receive_date'] ?? null;
        $params['receive_time'] = $args['receive_time'] ?? null;

        if($params['receive_date'] && $params['receive_time']) {
            $params['receive_date'] = createTimestamp($params['receive_date'], $params['receive_time']);
        }

        return $params;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $request = self::fetch($params);
        if(is_string($request['detail'])) {
            $detail = json_decode($request['detail']);
        }
        else {
            $detail = $request['detail']; 
        }

        if($request['status'] == 1) {
            $option = [
                'warehouse_id' => $request['warehouse_id']
            ];
            $cek_kapasitas = WarehouseStockDetail::cek_kapasitas($detail, $option);
            $cek_pallet_keluar = WarehouseStockDetail::cek_pallet_keluar($detail, $option);
            if($cek_kapasitas !== true) {
                return $cek_kapasitas;
            }
            else if($cek_pallet_keluar !== true){
                return $cek_pallet_keluar;
            }
        }

        $code = new TransactionCode($request['company_id'], 'warehouseReceipt');
        $code->setCode();
        $trx_code = $code->getCode();
        $attachment = [];
        if($request['files']) {

            $file=$request['files'];
            if(is_array($file)) {
                $c = 0;
                foreach($file as $image) {
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
        }

        if($request['ttd']) {
            $ttd_file = $request['ttd'];
            $ext = $ttd_file->getClientOriginalExtension();

            if( $ext == null OR $ext == '') {
            $ext = 'png';
            }
            $ttd = 'TTD' . date('Ymd_His') . str_random(10) . '.' . $ext;
            Image::make( $ttd_file->getRealPath() )->save(public_path('files/' . $ttd));
        } else {
            $ttd = null;
        }
      
        if($request['receiver'] == 'undefined') {
            $request['receiver'] = null;
        }

        $warehouse_receipt_id = DB::table(self::$table)->insertGetId([
            'company_id' => $request['company_id'],
            'purchase_order_id' => $request['purchase_order_id'],
            'customer_id' => $request['customer_id'],
            'receipt_type_id' => $request['receipt_type_id'],
            'is_overtime' => $request['is_overtime'] ?? 0,
            'warehouse_id' => $request['warehouse_id'],
            'city_to' => $request['city_to'],
            'sender' => $request['sender'],
            'receiver' => $request['receiver'],
            'warehouse_staff_id' => auth()->id(),
            // 'collectible_id' => $request['collectible_id'],
            'reff_no' => $request['reff_no'],
            'code' => $trx_code,
            'receive_date' => $request['receive_date'],
            'is_export' => $request['is_export'],
            'description' => $request['description'],
            'create_by' => auth()->id(),
            'nopol' => $request['nopol'],
            'driver' => $request['driver'],
            'ttd' => $ttd,
            'phone_number' => $request['phone_number'],
            'vehicle_type_id' => $request['vehicle_type_id'],
            'status' => $request['status']
        ]);

        self::storeShipmentStatus($warehouse_receipt_id);
        self::storePenerima(($request['receiver'] ?? 'N/C'), $request['city_to'], $request['company_id']);
        // Validasi handling area
      

        if(is_array($detail)) {
            if(count($detail) > 0) {
                foreach ($detail as $value) {
                    if (empty($value)) {
                        continue;
                    }

                    $params = (array) $value;
                    $params["is_pallet"] = $request["is_pallet"] ?? 0;

                    WarehouseReceiptDetail::store($params, $warehouse_receipt_id);

                    if($request['status'] == 1 && isset($value->item_id)) {
                        DB::table('items')->whereId($value->item_id)->update([
                          'wide' => ($value->wide ?? 0) ,
                          'long' => ($value->long ?? 0) ,
                          'height' => ($value->high ?? 0) ,
                          'volume' => ($value->wide ?? 0) * ($value->long ?? 0) * ($value->high ?? 0) / 1000000,
                          'tonase' => $value->weight
                        ]);
                    }
                }
            }
        }

        // Simpan lampiran
        if(is_array($attachment)) {
            if(count($attachment) > 0) {

                foreach($attachment as $unit) {
                    DB::table('delivery_order_photos')->insert([
                        'receipt_id' => $warehouse_receipt_id,
                        'name' => $unit
                    ]);
                }
            }
        }

        if(($request['purchase_order_id'] ?? null)) {
            PurchaseOrder::finishReceipt($request['purchase_order_id']);
        }

        return $warehouse_receipt_id;
    }

    /*
      Date : 24-03-2020
      Description : Membuat shipment status
      Developer : Didin
      Status : Create
    */
    public static function storeShipmentStatus($warehouse_receipt_id)
    { 
        DB::table('shipment_statuses')
        ->insert([
            'warehouse_receipt_id' => $warehouse_receipt_id,
            'status_date' => DB::raw('DATE_FORMAT(NOW(), "%Y-%m-%d")')
        ]);
    }

    /*
      Date : 20-03-2020
      Description : Menyimpan penerima di master kontak
      Developer : Didin
      Status : Create
    */
    public static function storePenerima($name, $address, $company_id)
    {
        $latest_contact = DB::table('contacts')
        ->whereName($name)
        ->whereIsPenerima(1)
        ->count('id');

        if($latest_contact == 0) {
            DB::table('contacts')
            ->insert([
              'company_id' => $company_id,
              'is_penerima' => 1,
              'name' => $name,
              'address' => $address ?? '-',
              'email' => Str::random(10) . '@gmail.com'
            ]);
        } else {
            DB::table('contacts')
            ->whereName($name)
            ->whereIsPenerima(1)
            ->update([
                  'address' => $address
            ]);
        }
    }

    /*
      Date : 29-08-2020
      Description : Menambah barang pada stock transaction
      Developer : Didin
      Status : Create
    */
    public static function bill($job_order_id) {
        try {
            $jobOrder = DB::table('job_orders')
            ->join('kpi_statuses', 'kpi_statuses.id', 'job_orders.kpi_id')
            ->where('job_orders.id', $job_order_id)
            ->select('job_orders.kpi_id', 'kpi_statuses.is_done')
            ->first();

            $jobOrderDetails = DB::table('job_order_details')
            ->join('warehouse_receipt_details', 'job_order_details.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
            ->where('job_order_details.header_id', $job_order_id)
            ->groupBy('warehouse_receipt_details.header_id')
            ->select('warehouse_receipt_details.header_id AS warehouse_receipt_id')
            ->get();
            if($jobOrder->is_done == 1) {
                $kpiLog = DB::table('kpi_logs')
                ->whereJobOrderId($job_order_id)
                ->whereKpiStatusId($jobOrder->kpi_id)
                ->select('date_update')
                ->first();
                foreach ($jobOrderDetails as $item) {
                    $warehouse_receipt_id = $item->warehouse_receipt_id;
                    $exist = DB::table('warehouse_receipt_billings')
                    ->whereWarehouseReceiptId($warehouse_receipt_id)
                    ->whereJobOrderId($job_order_id)
                    ->count('id');

                    $billing_date = Carbon::parse($kpiLog->date_update)->format('Y-m-d');
                    $new_receive_date = Carbon::parse($billing_date)
                    ->addDays(1)
                    ->format('Y-m-d');

                    if($exist == 0) {
                        $params = ['warehouse_receipt_id' => $warehouse_receipt_id, 'job_order_id' => $job_order_id, 'billing_date' => $billing_date, 'new_receive_date' => $new_receive_date, 'created_at' => Carbon::now()->format('Y-m-d')];
                        DB::table('warehouse_receipt_billings')
                        ->insert($params);
                    } else {
                        $params = ['billing_date' => $billing_date, 'new_receive_date' => $new_receive_date, 'updated_at' => Carbon::now()->format('Y-m-d')];
                        DB::table('warehouse_receipt_billings')
                        ->whereWarehouseReceiptId($warehouse_receipt_id)
                        ->whereJobOrderId($job_order_id)
                        ->update($params);
                    }
                }
            } else {
                foreach ($jobOrderDetails as $item) {
                    $warehouse_receipt_id = $item->warehouse_receipt_id;
                    DB::table('warehouse_receipt_billings')
                    ->whereWarehouseReceiptId($warehouse_receipt_id)
                    ->whereJobOrderId($job_order_id)
                    ->delete();
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage);
        }
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('warehouse_receipts')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Warehouse receipt not found');
        }
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan detail data
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = WR::with('customer:id,name,email', 'company:id,name', 'collectible','warehouse','staff')
        ->leftJoin('receipt_types', 'receipt_types.id', 'warehouse_receipts.receipt_type_id')
        ->leftJoin('purchase_orders', 'purchase_orders.id', 'warehouse_receipts.purchase_order_id')
        ->where('warehouse_receipts.id', $id)
        ->leftJoin('vehicle_types', 'vehicle_types.id', 'warehouse_receipts.vehicle_type_id')
        ->selectRaw('warehouse_receipts.*, vehicle_types.name AS vehicle_type_name, receipt_types.code AS receipt_type_code, receipt_types.name AS receipt_type_name, purchase_orders.code AS purchase_order_code')
        ->first();
        if($dt) {
            if($dt->customer) {
                if($dt->customer->email) {
                    $dt->customer->email = str_replace(',', ';', $dt->customer->email);
                }
            }
        }
        
        $dt->item_migration_id = ItemMigrationReceipt::getPrimaryId($id);
        $dt->voyage_schedule_id = VoyageReceipt::getPrimaryId($id);

        $dt->ttd = $dt->ttd != null ? asset('files') . '/' . $dt->ttd : null; 
        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $status = WarehouseReceiptStatus::getApproved();
        if($dt->status == $status) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function scanBarcode($code) {
        $code = preg_replace("/^(0+)([1-9]+0*)/", '${2}', $code);
        $warehouse_receipt_detail_id = $code;
        $dt = WarehouseReceiptDetail::query();
        $dt->where("warehouse_receipt_details.id", $warehouse_receipt_detail_id);

        $params = [];

        if($dt->count(self::$table . '.id') > 0) {
            $dt = $dt->leftJoin('purchase_orders', 'warehouse_receipts.purchase_order_id', 'purchase_orders.id');
            $dt = $dt->leftJoin('contacts', 'warehouse_receipts.customer_id', 'contacts.id');
            $dt = $dt->leftJoin('users', 'users.id', 'warehouse_receipts.create_by');
            $dt = $dt->leftJoin('vehicle_types', 'vehicle_types.id', 'warehouse_receipts.vehicle_type_id');

            $dt = $dt->select(
                self::$table . '.code',
                self::$table . '.description',
                self::$table . '.receive_date',
                self::$table . '.stripping_done',
                'racks.code AS rack_code',
                'purchase_orders.code AS purchase_order_code',
                'contacts.name AS customer_name',
                'warehouses.name AS warehouse_name',
                'warehouse_receipt_details.id AS warehouse_receipt_detail_id',
                'users.name AS creator_name',
                'warehouse_receipt_details.item_id',
                'warehouse_receipt_details.item_name',
                'vehicle_types.name AS vehicle_type_name',
                DB::raw('IF(warehouse_receipts.status = 0, "Draft", "Disetujui") AS status_name')
            )
            ->first();

            $dt->stock = StockTransaction::getAvailibity($dt->warehouse_receipt_detail_id);

            $params['code'] = $dt->code;
            $params['description'] = $dt->description;
            $params['receive_date'] = $dt->receive_date;
            $params['stripping_done'] = $dt->stripping_done;
            $params['purchase_order_code'] = $dt->purchase_order_code;
            $params['customer_name'] = $dt->customer_name;
            $params['warehouse_name'] = $dt->warehouse_name;
            $params['creator_name'] = $dt->creator_name;
            $params['vehicle_type_name'] = $dt->vehicle_type_name;
            $params['status_name'] = $dt->status_name;
            $params['item_name'] = $dt->item_name;
            $params['stock'] = $dt->stock;
            $params['rack_code'] = $dt->rack_code;
        }

        return $params;
    }
}

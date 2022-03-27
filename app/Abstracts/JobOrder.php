<?php

namespace App\Abstracts;

use Carbon\Carbon;
use App\Abstracts\Setting\Math;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\JobOrderDetail;
use App\Abstracts\Operational\WorkOrderDetail;
use App\Abstracts\Operational\ManifestDetail;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Operational\JobOrderContainer;
use App\Abstracts\Sales\SalesOrder;
use Exception;
use Illuminate\Http\Request;
use App\Model\JobOrder AS J;
use App\Abstracts\WorkOrder;
use App\Abstracts\Contact;
use Illuminate\Support\Facades\DB;

class JobOrder
{
    protected static $table = 'job_orders';

    public static function getRequestedDistance($id) {
        self::validate($id);
        $r = 0;
        $manifests = Manifest::query(['job_order_id' => $id]);
        $manifests = $manifests->groupBy('manifests.route_id');
        $manifests = $manifests->get();
        $r = $manifests->sum('distance');

        return $r;
    }

    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $dt = DB::table(self::$table);
        $dt = $dt->join('contacts AS customers', 'customers.id',self::$table .  '.customer_id');

        if($request['delivery_order_id']) {
            $inScope = ManifestDetail::query(['delivery_order_id' => $request['delivery_order_id']]);
            $inScope = $inScope->pluck('job_orders.id');
            $dt = $dt->whereIn(self::$table . '.id', $inScope);
        }


        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['delivery_order_id'] = $args['delivery_order_id'] ?? null;

        return $params;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung berat volume
      Developer : Didin
      Status : Create
    */
    public static function adjustVolumetricWeight($job_order_id) {
        $job_order_detail = DB::table('job_order_details')
        ->whereHeaderId($job_order_id)
        ->get();

        foreach($job_order_detail as $j) {
            $params = [];
            $volumetric_weight = $j->long * $j->wide * $j->high * $j->qty / 6000;
            $params['volumetric_weight'] = (string) $volumetric_weight;
            if($j->imposition == 2) {
                if($j->weight < $volumetric_weight) {
                    $total_price = $volumetric_weight * $j->price;
                } else {
                    $total_price = $j->weight * $j->price;
                }
                $params['total_price'] = $total_price;
            }
            DB::table('job_order_details')
            ->whereId($j->id)
            ->update($params);
        }
    }

    /*
      Date : 29-08-2020
      Description : Mengeluarkan barang dari stok berdasarkan dari job order
      Developer : Didin
      Status : Create
    */
    public static function decreaseStock($job_order_id) {
        try {

            $jobOrder = DB::table('job_orders')
            ->join('kpi_statuses', 'kpi_statuses.id', 'job_orders.kpi_id')
            ->where('job_orders.id', $job_order_id)
            ->select('kpi_statuses.is_done', 'job_orders.customer_id')
            ->first();
            $prevJobOrder = DB::table('kpi_logs')
            ->join('kpi_statuses', 'kpi_statuses.id', 'kpi_logs.kpi_status_id')
            ->where('kpi_logs.job_order_id', $job_order_id)
            ->orderBy('kpi_logs.created_at', 'DESC')
            ->skip(1)
            ->select('kpi_statuses.is_done')
            ->first();
            if($prevJobOrder) {
                if($prevJobOrder->is_done == 0 && $jobOrder->is_done == 1) {
                    $jobOrderDetails = DB::table('job_order_details')
                    ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
                    ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
                    ->where('job_order_details.header_id', $job_order_id)
                    ->whereNotNull('job_order_details.warehouse_receipt_detail_id')
                    ->select('job_order_details.qty', 'warehouse_receipts.warehouse_id', 'warehouse_receipt_details.item_id',  'warehouse_receipt_details.rack_id', 'job_order_details.warehouse_receipt_detail_id')
                    ->get();
                    foreach ($jobOrderDetails as $item) {
                        $params = ['item_id' => $item->item_id, 'warehouse_receipt_detail_id' => $item->warehouse_receipt_detail_id];
                        $stock = StockTransaction::cekStok($params);
                        if($stock - $item->qty < 0) {
                            throw new Exception('Tidak dapat mengurangi stok, karena stok barang kurang dari jumlah barang yang akan dikeluarkan');
                        } else {
                            $insert = [
                                'date_transaction' => Carbon::now()->format('Y-m-d'),
                                'customer_id' => $jobOrder->customer_id,
                                'item_id' => $item->item_id,
                                'warehouse_id' => $item->warehouse_id,
                                'rack_id' => $item->rack_id,
                                'warehouse_receipt_detail_id' => $item->warehouse_receipt_detail_id,
                                'qty_keluar' => $item->qty,
                                'qty_masuk' => 0,
                                'type_transaction_id' => 21,
                                'description' => 'Pengeluaran barang dari job order'
                            ];
                            DB::table('stock_transactions')
                            ->insert($insert);
                        }
                    }
                } else if($prevJobOrder->is_done == 1 && $jobOrder->is_done == 0) {
                    $jobOrderDetails = DB::table('job_order_details')
                    ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
                    ->where('job_order_details.header_id', $job_order_id)
                    ->whereNotNull('job_order_details.warehouse_receipt_detail_id')
                    ->select('job_order_details.qty', 'warehouse_receipt_details.item_id',  'warehouse_receipt_details.rack_id', 'job_order_details.warehouse_receipt_detail_id')
                    ->get();
                    foreach ($jobOrderDetails as $item) {
                        $params = ['item_id' => $item->item_id, 'warehouse_receipt_detail_id' => $item->warehouse_receipt_detail_id];
                        $insert = [
                            'date_transaction' => Carbon::now()->format('Y-m-d'),
                            'customer_id' => $jobOrder->customer_id,
                            'item_id' => $item->item_id,
                            'rack_id' => $item->rack_id,
                            'warehouse_receipt_detail_id' => $item->warehouse_receipt_detail_id,
                            'qty_masuk' => $item->qty,
                            'qty_keluar' => 0,
                            'type_transaction_id' => 21,
                            'description' => 'Pembatalan pengeluaran barang dari job order'
                        ];
                        DB::table('stock_transactions')
                        ->insert($insert);
                    }
                }
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage);
        }
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('job_orders')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan detail job order
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = J::with('trayek:id,name','moda:id,name','collectible','container_type:id,code,name,size,unit','vehicle_type','customer:id,name','service', 'sender:id,name,address','receiver:id,name,address','service.service_type','work_order', 'invoice_detail.invoice:id,code,status')
        ->leftJoin('companies', 'companies.id', 'job_orders.company_id')
        ->where('job_orders.id', $id)
        ->select('job_orders.*', 'companies.name AS company_name');
        $dt = $dt->first();
        $dt->additional = json_decode($dt->additional);

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Set tanggal masuk barang
      Developer : Didin
      Status : Create
    */
    public static function setReceiveDate($job_order_id) {
        try {
            $jobOrderDetails = DB::table('job_order_details')
            ->join('job_orders', 'job_orders.id', 'job_order_details.header_id')
            ->where('job_order_details.header_id', $job_order_id)
            ->select('job_orders.shipment_date', 'job_order_details.id', 'job_order_details.warehouse_receipt_detail_id')
            ->get();
            foreach ($jobOrderDetails as $detail) {

                if($detail->warehouse_receipt_detail_id == null) {
                    $params = ['receive_date' => Carbon::parse($detail->shipment_date)->format('Y-m-d H:i:s')];
                } else {
                    $warehouseReceipt = DB::table('warehouse_receipts')
                    ->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
                    ->where('warehouse_receipt_details.id', $detail->warehouse_receipt_detail_id)
                    ->select('warehouse_receipts.id', 'warehouse_receipts.receive_date')
                    ->first();

                    $billing = DB::table('warehouse_receipt_billings')
                    ->whereWarehouseReceiptId($warehouseReceipt->id)
                    ->orderBy('id', 'DESC')
                    ->first();

                    if($billing == null) {
                        $params = ['receive_date' => $warehouseReceipt->receive_date];
                    } else {
                        $params = ['receive_date' => Carbon::parse($billing->new_receive_date)->format('Y-m-d H:i:s')];
                    }
                }
                DB::table('job_order_details')
                ->whereId($detail->id)
                ->update($params);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage);
        }
    }

    public static function setSize($job_order_id) {
        $items = DB::table('job_order_details')
        ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
        ->where('job_order_details.header_id', $job_order_id)
        ->select('job_order_details.id', 'job_order_details.qty', 'warehouse_receipt_details.weight', 'warehouse_receipt_details.long', 'warehouse_receipt_details.wide', 'warehouse_receipt_details.high')
        ->get();

        foreach ($items as $item) {
            $param = [];
            $param['long'] = $item->long;
            $param['wide'] = $item->wide;
            $param['high'] = $item->high;
            $param['weight'] = $item->qty * $item->weight;
            DB::table('job_order_details')
            ->whereId($item->id)
            ->update($param);
        }
    }

    /*
      Date : 20-01-2021
      Description : Menambah status default
      Developer : Didin
      Status : Create
    */
    public static function setDefaultStatus($id) {
        $jo = DB::table('job_orders')
        ->whereId($id)
        ->first();
        if($jo) {
            $service = DB::table('services')
            ->whereId($jo->service_id)
            ->first();

            if(($service->kpi_status_id ?? null)) {
                $params = [];
                $params['update_date'] = Carbon::now()->format('d-m-Y');
                $params['update_time'] = Carbon::now()->format('H:i:s');
                $params['kpi_status_id'] = $service->kpi_status_id;
                $jobOrder = new \App\Http\Controllers\Operational\JobOrderController();
                $jobOrder->add_status(new Request($params), $id);
            }
        }
    }

    /*
      Date : 16-03-2020
      Description : Menambah KPI Status pada job order secara otomatis
      Developer : Didin
      Status : Edit
    */
    public static function autoAddStatus($id) {
        $jo = DB::table('job_orders')
        ->whereId($id)
        ->first();

        if($jo) {
            $service_id = $jo->service_id;
            $kpi_status = DB::table('kpi_statuses')
            ->whereId($jo->kpi_id)
            ->first();
            if($kpi_status) {
                $statuses = DB::table('kpi_statuses')
                ->whereServiceId($service_id)
                ->orderBy('id', 'asc');
                if($kpi_status->is_done == 1) {
                    $statuses = $statuses->whereIsDone(0);
                } else if($kpi_status->is_done == 0) {
                    $statuses = $statuses->whereIsDone(1);
                }
                $statuses = $statuses->first();
                if($statuses) {
                    $params = [];
                    $params['update_date'] = Carbon::now()->format('d-m-Y');
                    $params['update_time'] = Carbon::now()->format('H:i:s');
                    $params['kpi_status_id'] = $statuses->id;
                    $jobOrder = new \App\Http\Controllers\Operational\JobOrderController();
                    $jobOrder->add_status(new Request($params), $id);
                }
            }
        }
    }

    /*
      Date : 16-03-2020
      Description : Menyimpan job order
      Developer : Didin
      Status : Edit
    */
    public static function store(Request $request) {
        $params = [];
        $params['company_id'] = $request->company_id;
        $params['customer_id'] = $request->customer_id;
        $params['quotation_id'] = $request->quotation_id;
        $params['service_id'] = $request->service_id;
        $params['no_po_customer'] = $request->no_po_customer;
        $params['description'] = $request->description;
        $params['shipment_date'] = Carbon::parse($request->shipment_date)->format('Y-m-d');

        if(!$params['customer_id']) {
            throw new Exception('Customer is required !');
        }

        if(!$params['company_id']) {
            throw new Exception('Company is required !');
        }

        $service = DB::table('services')
        ->whereId($request->service_id)
        ->first();
        $params['service_type_id'] = $service->service_type_id ?? null;
        $params['create_by'] = auth()->id();
        $params['created_at'] = Carbon::now();

        $job_order_id = DB::table('job_orders')->insertGetId($params);

        if(is_array($request->detail)) {
            foreach($request->detail as $d) {
                if($d) {
                    JobOrderDetail::store(new Request($d), $job_order_id);
                }
            }
        }

        self::setKpiStatus($job_order_id);

        self::validasiLimitPiutang($job_order_id);

        return $job_order_id;
    }

      /*
      Date : 16-03-2020
      Description : Meng-inisiasi kpi status
      Developer : Didin
      Status : Edit
    */
      public static function setKpiStatus($job_order_id) {
         $job_order = DB::table('job_orders')
         ->whereId($job_order_id)
         ->first();
         if($job_order) {
            $service_id = $job_order->service_id;
            $ks=DB::table('kpi_statuses')
            ->whereRaw("service_id = $service_id")
            ->orderBy('sort_number','asc')
            ->first();
            if(!$ks) {
                throw new Exception('Status process in this service is empty');
            }
            $klog = DB::table('kpi_logs')
            ->insert([
                'kpi_status_id' => $ks->id,
                'job_order_id' => $job_order_id,
                'company_id' => $job_order->company_id,
                'create_by' => auth()->id(),
                'date_update' => Carbon::now()
            ]);

            DB::table('job_orders')
            ->whereId($job_order_id)
            ->update(['kpi_id' => $ks->id]);
        }
    }

    /*
      Date : 12-02-2021
      Description : Menyimpan data additional
      Developer : Didin
      Status : Create
    */
    public static function storeAdditional($params = [], $id) {
        $trans = self::show($id);
        $origin = $trans->additional;


        $keys = collect(array_keys($params));
        $transKeys = AdditionalField::indexKey('jobOrder');
        $keys = $keys->intersect($transKeys);

        $data = [];

        foreach ($keys as $k) {
            $data[$k] = $params[$k];
        }

        $params = $data;
        $params = collect($params)->union($origin);
        $params = $params->all();
        $json = json_encode($params);
        $update['additional'] = $json;
        DB::table('job_orders')
        ->whereId($id)
        ->update($update);
    }

    public static function setNullableAdditional($id) {
        $dt = self::show($id);
        if(!$dt->additional) {
            $data['additional'] = '{}';
            DB::table('job_orders')
            ->whereId($id)
            ->update($data);
        }
    }

    public static function setNullableAdditionals() {
        $data['additional'] = '{}';
        DB::table('job_orders')
        ->whereNull('additional')
        ->update($data);
    }



    /*
      Date : 06-03-2020
      Description : Menyimpan cost dari price list ke job order
      Developer : Didin
      Status : Create
    */
    public static function store_price_list_cost($job_order_id) {
        $jo = DB::table('job_orders')
        ->whereId($job_order_id)
        ->first();
        $wod = DB::table('work_order_details')
        ->whereId($jo->work_order_detail_id)
        ->first();

        if($wod->price_list_id != null) {
            $price_list_costs = DB::table('price_list_costs')
            ->whereHeaderId($wod->price_list_id)
            ->get();
            foreach ($price_list_costs as $vls) {
                DB::table('job_order_costs')->insert([
                    'header_id' => $job_order_id,
                    'cost_type_id' => $vls->cost_type_id,
                    'transaction_type_id' => 21,
                    'vendor_id' => $vls->vendor_id,
                    'qty' => $vls->qty,
                    'price' => $vls->price,
                    'total_price' => $vls->total_price,
                    'description' => $vls->description,
                    'type' => 1,
                    'is_edit' => 1,
                    'status' => 1,
                    'create_by' => auth()->id()
                ]);
            }
        }
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga job order
      Developer : Didin
      Status : Create
    */
    public static function countPrice($id) {
        self::setReceiveDate($id);
        $dt = self::show($id);
        if($dt->work_order_detail_id) {
            $wod = WorkOrderDetail::getPriceInfo($dt->work_order_detail_id);
            if($wod['service_type_id'] == 12 || $wod['service_type_id'] == 13) {
                self::countHandlingPrice($id);
            } else if($wod['service_type_id'] == 15) {
                self::countWarehouserentPrice($id);
            }
        } else {
            if(SalesOrder::hasJobOrder($id)) {
                SalesOrder::countPriceByJobOrder($id);
            }
        }
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga handling/ stuffing
      Developer : Didin
      Status : Create
    */
    public static function countHandlingPrice($id) {
        
        $dt = self::show($id);
        $wod = WorkOrderDetail::getPriceInfo($dt->work_order_detail_id);
        if($wod['handling_type'] == 1) {
            $items = self::getPriceDetails($id);
            foreach($items as $item) {
                DB::table('job_order_details')
                ->whereId($item->id)
                ->update([
                    'price' => $item->price, 
                    'total_price' => $item->total_price 
                ]);
            }
        } elseif($wod['handling_type'] == 2) {
            self::countContainerPrice($id);
        }
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga container
      Developer : Didin
      Status : Create
    */
    public static function countContainerPrice($id) {
        $dt = self::show($id);
        $wod = WorkOrderDetail::getPriceInfo($dt->work_order_detail_id);
        $container = JobOrderContainer::index($id);
        $qty = count($container);
        $jo_price = $wod['price_full'];
        $jo_total_price = $qty * $jo_price;

        $items = JobOrderDetail::index($id);
        $params = [];
        $params['imposition'] = 3;
        $total_item = $items->sum('qty');
        $params['price_item'] = $jo_total_price / $total_item;
        $grandtotal = 0;
        foreach($items as $i => $item) {
            $params['qty'] = $item->qty;
            $total_price = Math::countItemPrice($params);
            $grandtotal += $total_price;
            $price = Math::getItemPrice($params);
            if(Math::isChargeInMinimum($params)) {
                if($i == count($items) - 1) {
                    $offset = $grandtotal - $jo_total_price;
                    $total_price += $offset;
                }
            }

            DB::table('job_order_details')
            ->whereId($item->id)
            ->update([
                'price' => $price,
                'total_price' => $total_price
            ]);
        }

        DB::table('job_orders')
        ->whereId($id)
        ->update([
            'price' => $jo_price, 
            'total_price' => $jo_total_price 
        ]);

        return $total_price;
    }

    /*
      Date : 29-08-2020
      Description : Mendapatkan rincian harga barang
      Developer : Didin
      Status : Create
    */
    public static function getPriceDetails($id) {
        $jo = self::show($id);
        $wod = WorkOrderDetail::getPriceInfo($jo->work_order_detail_id);
        $masterItems = JobOrderDetail::index($id);
        $idx = 0;
        $inItems = $masterItems->groupBy('warehouse_receipt_id');
        foreach($inItems as $items) {
            $total_volume = $items->sum('volume');
            $total_tonase = $items->sum('weight');
            $total_item = $items->sum('qty');
            $grandtotal = 0;
            foreach ($items as $i => $item) {
                $params = [];
                $params['imposition'] = $item->imposition;
                $params['qty'] = $item->qty;
                $params['volume'] = $item->volume;
                $params['weight'] = $item->weight;
                $params['price_item'] = $wod['price_item'];
                $params['price_volume'] = $wod['price_volume'];
                $params['price_tonase'] = $wod['price_tonase'];

                $params['min_item'] = $wod['min_item'];
                $params['min_volume'] = $wod['min_volume'];
                $params['min_tonase'] = $wod['min_tonase'];

                $params['total_item'] = $total_item;
                $params['total_volume'] = $total_volume;
                $params['total_tonase'] = $total_tonase;
                $total_price = Math::countItemPrice($params);
                $price = Math::getItemPrice($params);
                $grandtotal += $total_price;
                if(Math::isChargeInMinimum($params)) {
                    if($i == count($items) - 1) {
                        $arg = [];
                        $arg['imposition'] = $params['imposition'];
                        $arg['qty'] = $params['min_item'];
                        $arg['volume'] = $params['min_volume'];
                        $arg['weight'] = $params['min_tonase'];
                        $arg['price_item'] = $params['price_item'];
                        $arg['price_volume'] = $params['price_volume'];
                        $arg['price_tonase'] = $params['price_tonase'];

                        $max_price = Math::countItemPrice($arg);
                        $offset = $grandtotal - $max_price;
                        $total_price += $offset;
                    }
                }

                $masterItems[$idx]->price = $price;
                $masterItems[$idx]->total_price = $total_price;

                $idx++;
            }

        }
        return $masterItems;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga handling/ stuffing
      Developer : Didin
      Status : Create
    */
    public static function countWarehouserentPrice($id) {
        self::countHandlingPrice($id);
        $items = JobOrderDetail::indexWithStock($id);
        $total = 0;
        foreach($items as $item) {
            if(($item->over_storage_day ?? null)) {
                $subtotal = $item->total_price * $item->over_storage_day;
                $total += $subtotal;
                DB::table('job_order_details')
                ->whereId($item->id)
                ->update([
                    'total_price' => $subtotal
                ]);
            }
        }
        return $total;
    }

    /*
      Date : 29-08-2020
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        $dt = self::show($id);

        if($dt->work_order_id) {
            WorkOrder::decreaseJO($dt->work_order_id);
        }

        if ($dt->service_type_id==1) {
            $detail = JobOrderDetail::query(['job_order_id' => $id]);
            $detail = $detail->select('job_order_details.*')->get();
            foreach ($detail as $value) {
                if ($value->imposition==1) {
                    $qty = $value->volume;
                } elseif ($value->imposition==2) {
                    $qty=  $value->weight;
                } else {
                    $qty = $value->qty;
                }
                WorkOrderDetail::increaseQty($qty, $dt->work_order_detail_id);
            }
        } else {
            WorkOrderDetail::increaseQty($dt->total_unit, $dt->work_order_detail_id);
        }

        ManifestDetail::clear($id);
        JobOrderDetail::clearStock($id);
        JobOrderDetail::clear($id);

        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi limit piutang ketika input jo
      Developer : Didin
      Status : Create
    */
    public static function validasiLimitPiutang($id, $getResponse = false) {
        $dt = self::show($id);
        $total_price = $dt->total_price;
        $resp = Contact::validasiLimitPiutang($dt->customer_id, $total_price, $getResponse);
        return $resp;
    }
}

<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\WorkOrder AS WO;

class WorkOrder
{
    protected static $table = 'work_orders';
    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('work_orders')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan detail work order
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = WO::with('quotation','quotation.sales:id,name', 'customer:id,name,company_id','customer.customer_service:id,name', 'customer.company:id,name','company:id,name','job_orders.invoice_detail.invoice')->where('id', $id)->first();
        $dt->additional = json_decode($dt->additional);

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Mengurangi JO
      Developer : Didin
      Status : Create
    */
    public static function decreaseJO($id) {
        self::validate($id);

        DB::table(self::$table)
        ->whereId($id)
        ->decrement('total_job_order');
    }

    /*
      Date : 14-09-2020
      Description : Memperoleh id job order dari paket work order
      Developer : Didin
      Status : Create
    */
    public static function fetchJobOrderIdPacket($id) {
        $jobPacket = DB::table('job_packets')
        ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
        ->where('work_order_details.header_id', $id)
        ->select('job_order_id')
        ->first();

        return $jobPacket->job_order_id ?? null;
    }
    /*
      Date : 14-09-2020
      Description : Menampikan rincian harga pada paket job order
      Developer : Didin
      Status : Create
    */
    public static function showPriceDetail($work_order_id) {
        $jobPackets = DB::table('job_packets')        
        ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
        ->leftJoin('price_lists', 'work_order_details.price_list_id', 'price_lists.id')
        ->leftJoin('services AS price_list_services', 'price_lists.service_id', 'price_list_services.id')
        ->leftJoin('quotation_details', 'work_order_details.quotation_detail_id', 'quotation_details.id')
        ->leftJoin('services AS quotation_detail_services', 'quotation_details.service_id', 'quotation_detail_services.id')
        ->where('work_order_details.header_id', $work_order_id)
        ->select(
            DB::raw('job_packets.*'), 
            DB::raw('COALESCE(price_list_services.service_type_id, quotation_details.service_type_id) AS service_type_id'),
            DB::raw('COALESCE(price_list_services.name, quotation_detail_services.name) AS service_name'),
            DB::raw('COALESCE(price_list_services.is_overtime, quotation_detail_services.is_overtime) AS is_overtime'),
            DB::raw('COALESCE(price_lists.handling_type, quotation_details.handling_type) AS handling_type'),
            DB::raw('COALESCE(price_lists.free_storage_day, quotation_details.free_storage_day) AS free_storage_day'),
            DB::raw('COALESCE(price_lists.over_storage_price, quotation_details.over_storage_price) AS over_storage_price'),
            DB::raw('COALESCE(price_lists.price_volume, quotation_details.price_inquery_handling_volume) AS price_volume'),
            DB::raw('COALESCE(price_lists.price_tonase, quotation_details.price_inquery_handling_tonase) AS price_tonase'),
            DB::raw('COALESCE(price_lists.price_item, quotation_details.price_inquery_item) AS price_item'),
            DB::raw('COALESCE(price_lists.price_full, quotation_details.price_inquery_full) AS price_borongan'),
            DB::raw('COALESCE(price_lists.min_volume, quotation_details.price_inquery_min_volume) AS min_volume'),
            DB::raw('COALESCE(price_lists.min_tonase, quotation_details.price_inquery_min_tonase) AS min_tonase'),
            DB::raw('COALESCE(price_lists.min_item, quotation_details.price_inquery_min_item) AS min_item')
        )
        ->get();
        $job_order_id = self::fetchJobOrderIdPacket($work_order_id);
        $jobOrder = DB::table('job_orders')
        ->join('kpi_statuses', 'kpi_statuses.id', 'job_orders.kpi_id')
        ->where('job_orders.id', $job_order_id)
        ->select('job_orders.kpi_id', 'kpi_statuses.is_done')
        ->first();
        if($jobOrder->is_done == 1) {
            $kpiLogs = DB::table('kpi_logs')
            ->whereJobOrderId($job_order_id)
            ->whereKpiStatusId($jobOrder->kpi_id)
            ->orderBy('date_update', 'DESC')
            ->first();
            $last= $kpiLogs->date_update;
        } else {
            $last = Carbon::now()->format('Y-m-d');
        }
        $jobPackets = $jobPackets->map(function($j) use($work_order_id, $job_order_id, $last){
            $use_container = false;
            if($j->service_type_id == 2 || $j->service_type_id == 3) {
                $use_container = true;
            } else if($j->service_type_id == 12 || $j->service_type_id == 13) {
                if($j->handling_type != 1) {
                    $use_container = true;
                } else {
                    $jobOrderDetails = DB::table('job_order_details')
                ->where('job_order_details.header_id', $job_order_id)
                ->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
                ->leftJoin('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
                ->select(
                    'job_order_details.item_name', 
                    'warehouse_receipts.id AS warehouse_receipt_id', 
                    'warehouse_receipts.code AS warehouse_receipt_code', 
                    'job_order_details.receive_date', 
                    'job_order_details.qty', 
                    'job_order_details.volume', 
                    'job_order_details.weight', 
                    'job_order_details.imposition', 
                    'job_order_details.warehouse_receipt_detail_id', 
                    DB::raw("'$last' AS outbound_date"), 
                    DB::raw('IF(job_order_details.imposition = 1, "Kubikasi", IF(job_order_details.imposition = 2, "Tonase", IF(job_order_details.imposition = 3, "Item", "Borongan"))) AS imposition_name'),
                    DB::raw('IF(job_order_details.imposition = 1, "m3", IF(job_order_details.imposition = 2, "Ton", IF(job_order_details.imposition = 3, "item", "borongan"))) AS unit_name')
                )
                ->get();
                $service_overtime = \App\Http\Controllers\Setting\SettingController::fetch('work_order', 'service_overtime')->value;
                $jobOrderDetails = $jobOrderDetails->map(function($item) use ($j, $service_overtime){
                    $detail = $item;
                    $isShow = false;
                    if($j->is_overtime == 1) {
                        if($detail->warehouse_receipt_detail_id) {
                            $warehouseReceipt = DB::table('warehouse_receipts')
                            ->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
                            ->where('warehouse_receipt_details.id', $detail->warehouse_receipt_detail_id)
                            ->select('warehouse_receipts.is_overtime')
                            ->first();

                            if(($warehouseReceipt->is_overtime ?? 0) == 1) {
                                $receive_date = Carbon::parse($detail->receive_date);
                                $overstart = Carbon::parse($detail->receive_date)->format('Y-m-d') . ' ' . $service_overtime;
                                $overstart = Carbon::parse($overstart);
                                $overend = Carbon::parse($detail->receive_date)->addDays(1)->format('Y-m-d') . ' 03:00:00' ;
                                $overend = Carbon::parse($overend);
                                if($receive_date->gt($overstart) && $receive_date->lt($overend)) {
                                    $isShow = true;
                                }
                            }
                        }
                    } else {
                        $isShow = true;
                    }
                    if($isShow) {
                        $qty = 0;
                        $minimum = 0;
                        if($item->imposition == 1) {
                            $qty = $item->volume;
                            $price = $j->price_volume;
                            $minimum = $j->min_volume;
                        } else if($item->imposition == 2) {
                            $qty = $item->weight;
                            $price = $j->price_tonase;
                            $minimum = $j->min_tonase;
                        } else if($item->imposition == 3) {
                            $qty = $item->weight;
                            $price = $j->price_item;
                            $minimum = $j->min_item;
                        } else if($item->imposition == 4) {
                            $qty = 1;
                            $price = $j->price_borongan;
                        }
                        $qty = number_format($qty, 3, '.', '');
                        if($item->imposition == 2) {
                            if($minimum > $qty / 1000) {
                                $total_price = $price * $minimum;
                            } else {
                                $total_price = $price * $qty / 1000;
                            }
                        } else {
                            if($minimum > $qty) {
                                $total_price = $price * $minimum;
                            } else {
                                $total_price = $price * $qty;
                            }
                        }

                        $item->minimum = $minimum;
                        $item->qty = $qty;
                        $item->price = $price;
                        $item->total_price = $total_price;
                    } else {
                        $item = null;
                    }

                    return $item;
                });
                $jobOrderDetails = $jobOrderDetails->filter(function($v){
                    return $v != null;
                });
                $jobOrderDetails = $jobOrderDetails->map(function($v){
                    if($v->imposition == 2) {
                        $v->total_price /= 1000;
                    }

                    return $v;
                });
                $j->items = $jobOrderDetails;
                $j->grandtotal = $jobOrderDetails->sum('total_price');
                $j->total_volume = DB::table('job_order_details')->whereHeaderId($job_order_id)->sum('volume');
                $j->total_tonase = DB::table('job_order_details')->whereHeaderId($job_order_id)->sum('weight');

                }
            } else if($j->service_type_id == 15) {
                $jobOrderDetails = DB::table('job_order_details')
                ->where('job_order_details.header_id', $job_order_id)
                ->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
                ->leftJoin('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
                ->select(
                    'job_order_details.item_name', 
                    'warehouse_receipts.id AS warehouse_receipt_id', 
                    'warehouse_receipts.code AS warehouse_receipt_code', 
                    'job_order_details.receive_date', 
                    'job_order_details.imposition', 
                    'job_order_details.qty', 
                    'job_order_details.volume', 
                    'job_order_details.weight', 
                    DB::raw("'$last' AS outbound_date"), 
                    DB::raw("DATEDIFF('$last', job_order_details.receive_date) + 1 AS durasi"),
                    DB::raw("IF(DATEDIFF('$last', job_order_details.receive_date) + 1 - " . $j->free_storage_day . " < 0, 0, DATEDIFF('$last', job_order_details.receive_date) + 1 - " . $j->free_storage_day . ") AS durasi_netto"),
                    DB::raw('IF(job_order_details.imposition = 1, "Kubikasi", IF(job_order_details.imposition = 2, "Tonase", IF(job_order_details.imposition = 3, "Item", "Borongan"))) AS imposition_name'),
                    DB::raw('IF(job_order_details.imposition = 1, "m3", IF(job_order_details.imposition = 2, "kg", IF(job_order_details.imposition = 3, "item", "borongan"))) AS unit_name'),
                    DB::raw($j->over_storage_price . " AS price"),
                    DB::raw('IF(job_order_details.imposition = 1, job_order_details.volume, IF(job_order_details.imposition = 2, job_order_details.weight, IF(job_order_details.imposition = 3, job_order_details.qty, 1))) * ' . $j->over_storage_price . ' * (IF(DATEDIFF("' . $last . '", job_order_details.receive_date) + 1 - ' . $j->free_storage_day . ' > 0, DATEDIFF("' . $last . '", job_order_details.receive_date) + 1 - ' . $j->free_storage_day . ', 0)) AS total_price')
                )
                ->get();

                $jobOrderDetails = $jobOrderDetails->map(function($v){
                    if($v->imposition == 2) {
                        $v->total_price /= 1000;
                    }

                    return $v;
                });
                
                $j->items = $jobOrderDetails;
                $j->grandtotal = $jobOrderDetails->sum('total_price');
                $j->total_volume = DB::table('job_order_details')->whereHeaderId($job_order_id)->sum('volume');
                $j->total_tonase = DB::table('job_order_details')->whereHeaderId($job_order_id)->sum('weight');
            }
            $j->use_container = $use_container;

            return $j;
        }) ;

        return $jobPackets;
    }

    public static function storeItemPacketPrice($work_order_id) {
        $workOrder = DB::table('work_orders')
        ->whereId($work_order_id)
        ->first();
        if($workOrder) {
            if($workOrder->is_job_packet == 1) {
                $job_order_id = self::fetchJobOrderIdPacket($work_order_id);
                $date = $workOrder->date;
                $jobOrderDetails = DB::table('job_orders') 
                ->join('services', 'services.id', 'job_orders.service_id')
                ->join('job_order_details', 'job_order_details.header_id', 'job_orders.id')
                ->join('kpi_statuses', 'kpi_statuses.id', 'job_orders.kpi_id')
                ->where('job_orders.id', $job_order_id)
                ->select('job_orders.id', 'services.service_type_id', 'job_order_details.warehouse_receipt_detail_id', 'job_order_details.qty', 'job_order_details.weight', 'job_order_details.volume', 'job_order_details.imposition', 'job_order_details.receive_date', 'job_order_details.warehouse_receipt_detail_id', 'job_orders.kpi_id', 'kpi_statuses.is_done')
                ->get();
                $oldestDate = DB::table('job_order_details')
                ->whereHeaderId($job_order_id)
                ->min('receive_date');
                if(count($jobOrderDetails) > 0) {
                    $jobOrder = $jobOrderDetails[0];
                    $jobPackets = DB::table('job_packets')
                    ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
                    ->whereJobOrderId($job_order_id)
                    ->select('job_packets.id', 'job_packets.work_order_detail_id', 'work_order_details.price_list_id', 'work_order_details.quotation_detail_id')
                    ->get();
                    foreach($jobPackets as $j) {
                        $isCount = false;
                        if($j->price_list_id != null) {
                            $priceList = DB::table('price_lists')
                            ->join('services', 'services.id', 'price_lists.service_id')
                            ->where('price_lists.id', $j->price_list_id)
                            ->select("price_volume","price_tonase", "price_item", "price_borongan", 'handling_type', 'free_storage_day', 'over_storage_price', 'services.service_type_id', 'service_id', 'min_tonase', 'min_volume', 'min_item')
                            ->first();
                            $free_storage_day = $priceList->free_storage_day ?? 0;

                            if($priceList->service_type_id == 15) {
                                $price_volume = $priceList->over_storage_price;
                                $price_tonase = $priceList->over_storage_price;
                                $price_item = $priceList->over_storage_price;
                                $price_borongan = $priceList->over_storage_price;
                            } else {       
                                $price_volume = $priceList->price_volume;
                                $price_tonase = $priceList->price_tonase;
                                $price_item = $priceList->price_item;
                                $price_borongan = $priceList->price_borongan;
                            }
                            $handling_type = $priceList->handling_type;
                            $service_type_id = $priceList->service_type_id;
                            $service_id = $priceList->service_id;
                            $min_tonase = $priceList->min_tonase;
                            $min_volume = $priceList->min_volume;
                            $min_item = $priceList->min_item;
                        } else {
                            $quotationDetail = DB::table('quotation_details')
                            ->join('services', 'services.id', 'quotation_details.service_id')
                            ->where('quotation_details.id', $j->quotation_detail_id)
                            ->select("price_inquery_handling_volume","price_inquery_handling_tonase", "price_inquery_item", "price_inquery_tonase", "price_inquery_volume", "price_inquery_full", 'handling_type', 'free_storage_day', 'over_storage_price', 'services.service_type_id', 'price_contract_min_tonase', 'price_contract_min_volume', 'price_contract_min_item', 'service_id')
                            ->first();

                            $free_storage_day = $quotationDetail->free_storage_day ?? 0;
                            if($quotationDetail->service_type_id == 15) {
                                $price_volume = $quotationDetail->over_storage_price;
                                $price_tonase = $quotationDetail->over_storage_price;
                                $price_item = $quotationDetail->over_storage_price;
                                $price_borongan = $quotationDetail->over_storage_price;
                            } else {
                                if($quotationDetail->service_type_id == 1) {
                                    $price_volume = $quotationDetail->price_inquery_volume;
                                    $price_tonase = $quotationDetail->price_inquery_tonase;
                                    $price_item = $quotationDetail->price_inquery_item;
                                    $price_borongan = $quotationDetail->price_inquery_full;
                                } else if($quotationDetail->service_type_id == 12 || $quotationDetail->service_type_id == 13) {
                                    $price_volume = $quotationDetail->price_inquery_handling_volume;
                                    $price_tonase = $quotationDetail->price_inquery_handling_tonase;
                                    $price_item = $quotationDetail->price_inquery_item;
                                    $price_borongan = $quotationDetail->price_inquery_full;
                                }
                            }
                            $handling_type = $quotationDetail->handling_type;
                            $service_type_id = $quotationDetail->service_type_id;
                            $service_id = $quotationDetail->service_id;
                            $min_tonase = $quotationDetail->price_contract_min_tonase;
                            $min_volume = $quotationDetail->price_contract_min_volume;
                            $min_item = $quotationDetail->price_contract_min_item;

                        }

                        $service = DB::table('services')
                        ->whereId($service_id)
                        ->first();

                        $is_overtime_service = $service->is_overtime;
                        if($service_type_id == 1 || $service_type_id == 15) {
                            $isCount = true;
                        } else if($service_type_id == 12 || $service_type_id == 13) {
                            if($handling_type == 1) {
                                $isCount = true;
                            }
                        }
                        $grandtotal = 0;
                        if($isCount) {
                            if($service_type_id == 15) {
                                if($jobOrder->is_done == 1) {
                                    $kpiLogs = DB::table('kpi_logs')
                                    ->whereJobOrderId($job_order_id)
                                    ->whereKpiStatusId($jobOrder->kpi_id)
                                    ->orderBy('date_update', 'DESC')
                                    ->first();
                                    $last= $kpiLogs->date_update;
                                } else {
                                    $last = Carbon::now()->format('Y-m-d');
                                }
                                
                            }
                            foreach($jobOrderDetails as $detail) {
                                if($service_type_id == 12 || $service_type_id == 13) {
                                    if($handling_type == 1) {
                                        if($detail->receive_date == $oldestDate) {
                                            if($min_volume > $detail->volume) {
                                                $detail->volume = $min_volume;
                                            }
                                            if($min_tonase * 1000 > $detail->weight) {
                                                $detail->weight = $min_tonase;
                                            }
                                            if($min_item > $detail->qty) {
                                                $detail->qty = $min_item;
                                            }
                                        }
                                    }
                                }

                                $detail->volume = number_format($detail->volume, 3, '.', '');
                                $detail->weight = number_format($detail->weight, 3, '.', '');
                                if($detail->imposition == 1) {
                                    $subtotal = $price_volume * $detail->volume;
                                } else if($detail->imposition == 2) {
                                    $subtotal = $price_tonase * $detail->weight / 1000;
                                } else if($detail->imposition == 3) {
                                    $subtotal = $price_item * $detail->qty;
                                } else if($detail->imposition == 4) {
                                    $subtotal = $price_borongan;
                                }
                                $duration = 1;
                                $date = $detail->receive_date;
                            
                                $warehouseReceipt = DB::table('warehouse_receipts')
                                ->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
                                ->where('warehouse_receipt_details.id', $detail->warehouse_receipt_detail_id)
                                ->select('warehouse_receipts.receive_date', 'warehouse_receipts.id')
                                ->first();
                                    
                                if($service_type_id == 15) {
                                    $timestamp = DB::select("SELECT DATEDIFF('$last', '$date') AS duration")[0];
                                    $duration = $timestamp->duration;
                                    $duration += 1;
                                    $duration -= $free_storage_day;
                                    if($duration < 1) {
                                        $duration = 0;
                                    }
                                }
                                $isUpdate = false;
                                if($is_overtime_service == 1) {
                                    if($detail->warehouse_receipt_detail_id) {
                                        $warehouseReceipt = DB::table('warehouse_receipts')
                                        ->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
                                        ->where('warehouse_receipt_details.id', $detail->warehouse_receipt_detail_id)
                                        ->select('warehouse_receipts.is_overtime')
                                        ->first();

                                        if(($warehouseReceipt->is_overtime ?? 0) == 1) {
                                            $service_overtime = \App\Http\Controllers\Setting\SettingController::fetch('work_order', 'service_overtime')->value;
                                            $receive_date = Carbon::parse($detail->receive_date);
                                            $overstart = Carbon::parse($detail->receive_date)->format('Y-m-d') . ' ' . $service_overtime;
                                            $overstart = Carbon::parse($overstart);
                                            $overend = Carbon::parse($detail->receive_date)->addDays(1)->format('Y-m-d') . ' 03:00:00' ;
                                            $overend = Carbon::parse($overend);
                                            if($receive_date->gt($overstart) && $receive_date->lt($overend)) {
                                                $isUpdate = true;
                                            }
                                        }
                                    }
                                } else {
                                    $isUpdate = true;
                                }
                                if($isUpdate) {
                                    $grandtotal += ($subtotal * $duration); 
                                }
                            }
                            $duration = 1;

                            DB::table('job_packets')
                            ->whereId($j->id)
                            ->update([
                                'duration' => $duration,
                                'price' => $grandtotal,
                                'total_price' => $grandtotal * $duration
                            ]);

                            
                        }

                    }
                }
            }
        }
        self::storePacketPrice($work_order_id);
    }

    public static function storePacketPrice($work_order_id) {
         $totalPacketPrice = DB::table('work_orders')
         ->join('work_order_details', 'work_order_details.header_id', 'work_orders.id')
         ->join('job_packets', 'work_order_details.id', 'job_packets.work_order_detail_id')
         ->where('work_orders.id', $work_order_id)
         ->sum('job_packets.total_price');
         $job_order_id = self::fetchJobOrderIdPacket($work_order_id);
         DB::table('job_orders') 
         ->whereId($job_order_id)
         ->update([
              'price' => $totalPacketPrice,
              'total_price' => $totalPacketPrice,
         ]);
    }

    public static function setNullableAdditionals() {
        $data['additional'] = '{}';
        DB::table('work_orders')
        ->whereNull('additional')
        ->update($data);
    }

    /*
      Date : 12-02-2021
      Description : Menyimpan data additional
      Developer : Didin
      Status : Create
    */
    public static function storeAdditional($params = [], $id) {
        if($params !== null) {
            
            $trans = self::show($id);
            $origin = $trans->additional;


            $keys = collect(array_keys($params));
            $transKeys = AdditionalField::indexKey('workOrder');
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
            DB::table('work_orders')
            ->whereId($id)
            ->update($update);
        } 
    }
}

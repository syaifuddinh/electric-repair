<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use App\Abstracts\JobOrder;
use App\Abstracts\Operational\WorkOrderDetail;
use App\Abstracts\Inventory\Item;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Imposition;
use App\Abstracts\Setting\Unit;
use App\Abstracts\Setting\Math;
use App\Exports\JobOrder\ImportItem;
use App\Imports\JobOrder\JobOrderDetailImport;
use Excel;
use App\Abstracts\Inventory\StockTransaction AS ST2;

class JobOrderDetail
{
    protected static $table = 'job_order_details';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $dt = DB::table(self::$table)
        ->leftJoin('manifest_details', 'manifest_details.job_order_detail_id', 'job_order_details.id')
        ->join('job_orders', 'job_orders.id', 'job_order_details.header_id');

        if($request['header_id']) {
            $dt = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menangkap parameter untuk filter
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params['header_id'] = $args['header_id'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan job order detail
      Developer : Didin
      Status : Create
    */
    public static function index($job_order_id = null) {
        $dt = self::query();
        $dt = $dt->leftJoin('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id');
        $dt = $dt->leftJoin('impositions', 'impositions.id', 'job_order_details.imposition');
        $dt = $dt->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id');
        if($job_order_id) {
            $dt = $dt->where('job_order_details.header_id', $job_order_id);
        }
        $dt = $dt->select('job_order_details.*', 'pieces.name as piece', 'impositions.name as imposition', 'warehouse_receipt_details.header_id AS warehouse_receipt_id')->get();
        
        return $dt;
    }

    public static function indexWithStock($job_order_id) {
        $jo = JobOrder::show($job_order_id);
        $stockTransactions = DB::raw('(SELECT SUM(qty_masuk - qty_keluar) AS stock, warehouse_receipt_detail_id FROM stock_transactions GROUP BY warehouse_receipt_detail_id) AS stock_transactions');

        $wod = WorkOrderDetail::query();
        $volumeCharge = DB::table('job_orders')
        ->leftJoinSub($wod, 'work_order_details', function($join){
            $join->on('work_order_details.id', 'job_orders.work_order_detail_id');
        });
        $volumeCharge = $volumeCharge->select('job_orders.id AS job_order_id', DB::raw('1 AS imposition'), 'work_order_details.min_volume AS minimum');

        $weightCharge = DB::table('job_orders')
        ->leftJoinSub($wod, 'work_order_details', function($join){
            $join->on('work_order_details.id', 'job_orders.work_order_detail_id');
        });
        $weightCharge = $weightCharge->select('job_orders.id AS job_order_id', DB::raw('2 AS imposition'), 'work_order_details.min_tonase AS minimum');


        $itemCharge = DB::table('job_orders')
        ->leftJoinSub($wod, 'work_order_details', function($join){
            $join->on('work_order_details.id', 'job_orders.work_order_detail_id');
        });
        $itemCharge = $itemCharge->select('job_orders.id AS job_order_id', DB::raw('3 AS imposition'), 'work_order_details.min_item AS minimum');

        $workOrderDetails = $volumeCharge->union($weightCharge)->union($itemCharge);

        $dt = DB::table('job_order_details')
        ->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id')
        ->leftJoin('warehouse_receipt_details', 'job_order_details.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
        ->leftJoin('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
        ->leftJoin('racks', 'racks.id', 'warehouse_receipt_details.rack_id')
        ->leftJoin('warehouses', 'warehouses.id', 'warehouse_receipts.warehouse_id');
        if($jo->service_type_id == 15) {
            $timeline = self::storageTimelineQuery($job_order_id);
            $dt = $dt->joinSub($timeline, 'timeline', function($join){
                $join->on('timeline.id', 'job_order_details.id');
            }); 
        }
        $dt = $dt->leftJoin($stockTransactions, 'stock_transactions.warehouse_receipt_detail_id', 'job_order_details.warehouse_receipt_detail_id');
        $dt = $dt->leftJoinSub($workOrderDetails, 'work_order_details', function($join){
            $join->on('work_order_details.job_order_id', 'job_order_details.header_id');
            $join->on('job_order_details.imposition', 'work_order_details.imposition');
        });
        $dt = $dt->where('job_order_details.header_id', $job_order_id);
        $dt = $dt->selectRaw(
          'job_order_details.*,
          pieces.name AS piece_name,
          COALESCE(work_order_details.minimum, 0) AS minimum,
          warehouse_receipts.id AS warehouse_receipt_id,
          warehouse_receipts.warehouse_id,
          warehouses.name AS warehouse_name,
          COALESCE(stock_transactions.stock, 0) AS stock,
          racks.code AS rack_code,
          (SELECT SUM(transported) FROM manifest_details JOIN manifests ON manifests.id = manifest_details.header_id WHERE job_order_detail_id = job_order_details.id) AS transported_item,
          job_order_details.qty - (SELECT SUM(transported) FROM manifest_details JOIN manifests ON manifests.id = manifest_details.header_id WHERE job_order_detail_id = job_order_details.id) AS leftover_item');
        if($jo->service_type_id == 15) {
            $dt = $dt->addSelect(['timeline.duration']);
            $dt = $dt->addSelect(['timeline.over_storage_day']);
            $dt = $dt->addSelect(['timeline.free_storage_day']);
        }
        $dt = $dt->get();
        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung berat volume
      Developer : Didin
      Status : Create
    */
    public static function store(Request $request, $job_order_id) {
        $params = [];
        $params['header_id'] = $job_order_id;
        $params['piece_id'] = $request->piece_id;
        $params['qty'] = $request->total_item;
        $params['long'] = $request->long ?? 0;
        $params['wide'] = $request->wide ?? 0;
        $params['high'] = $request->high ?? 0;
        $params['volume'] = $request->total_volume ?? 0;
        $params['weight'] = $request->total_tonase ?? 0;
        $params['item_name'] = $request->item_name;
        $params['imposition'] = $request->imposition;
        $params['barcode'] = $request->barcode ?? "";
        $params['description'] = $request->description;
        $params['leftover'] = $request->total_item;
        $params['weight_type'] = $request->weight_type;
        $params['warehouse_receipt_detail_id'] = $request->warehouse_receipt_detail_id;
        $params['rack_id'] = $request->rack_id;
        $params['item_id'] = $request->item_id;
        $params['quotation_id'] = $request->quotation_id ?? null;

        $params['create_by'] = auth()->id();
        DB::table('job_order_details')
        ->insert($params);
    }

    public static function storageTimelineQuery($job_order_id = null) {
        $dt = self::query();
        $wod = WorkOrderDetail::query();
        $dt = $dt->joinSub($wod, 'work_order_details', function($join){
            $join->on('work_order_details.id', 'job_orders.work_order_detail_id');
        });
        if($job_order_id) {
            $dt = $dt->where('job_order_details.header_id', $job_order_id);
        }
        $dt = $dt->select(
            'job_order_details.id', 
            'work_order_details.free_storage_day',
            DB::raw('DATEDIFF(COALESCE(job_order_details.load_date, job_order_details.receive_date), job_order_details.receive_date) + 1 as duration'),
            DB::raw('IF(DATEDIFF(COALESCE(job_order_details.load_date, job_order_details.receive_date), job_order_details.receive_date) + 1 - work_order_details.free_storage_day < 0, 0, DATEDIFF(COALESCE(job_order_details.load_date, job_order_details.receive_date), job_order_details.receive_date) + 1 - work_order_details.free_storage_day) AS over_storage_day'));

        return $dt;
    }

    public static function setContainerImposition() {
        $dt = self::query();
        $wod = WorkOrderDetail::query();
        $dt = $dt->joinSub($wod, 'work_order_details', function($join){
            $join->on('work_order_details.id', 'job_orders.work_order_detail_id');
        }) ;
        $dt = $dt->whereIn('work_order_details.service_type_id', [12, 13]);
        $dt = $dt->where('work_order_details.handling_type', 2);
        $dt = $dt->where('job_order_details.imposition', '!=', 3);
        $details = $dt->pluck('job_order_details.id');
        DB::table('job_order_details')
        ->whereIn('job_order_details.id', $details)
        ->update([
            'imposition' => 3
        ]);
    }


    /*
      Date : 14-03-2021
      Description : Download format import
      Developer : Didin
      Status : Create
    */
    public static function downloadImportItemExample() {
        $columns = [];
        $columns[] = ['name' => 'item_name'];
        $columns[] = ['name' => 'qty'];
        $columns[] = ['name' => 'charge_in'];
        $columns[] = ['name' => 'description'];

        return Excel::download(new ImportItem($columns), 'Format import item in job order.xlsx');
    }


    /*
      Date : 14-03-2021
      Description : Import item
      Developer : Didin
      Status : Create
    */
    public static function importItemWarehouse($file, $warehouse_id = null) {
        if(!$file) {
            throw new Exception('File is required');
        }

        $ext = $file->getClientOriginalExtension();
        if($ext != 'xls' && $ext != 'xlsx' ) {
            throw new Exception('Excel file is required');
        }
        $collection = (new JobOrderDetailImport)->toArray($file);
        $collection = $collection[0];
        $collection = array_splice($collection, 1, count($collection));
        $collection = collect($collection)
        ->filter(function($rows){
            $has_value = false;
            foreach ($rows as $idx => $row) {
                if($row !== '' && $row !== null) {
                    $has_value = true;
                }
            }
            return $has_value;
        })
        ->toArray();

        $res = self::adjustItemByExcel($collection);
        // $res = self::setSuggestionRack($res, $warehouse_id);

        return $res;
    }

    public static function adjustItemByExcel($items = []) {
        $items = Setting::trimArray2D($items);
        $columns = [];
        $columns[] = ['name' => 'item_name'];
        $columns[] = ['name' => 'qty'];
        $columns[] = ['name' => 'charge_in'];
        $columns[] = ['name' => 'description'];

        $params = [];
        foreach ($items as $item) {
            $param = [];
            foreach ($item as $i => $col) {
                if($i < count($columns)) {
                    $param[$columns[$i]['name']] = $col;
                }
            }
            $param = self::integrateExcelData($param);
            $params[] = $param;
        }
        return $params;
    }

    public static function integrateExcelData($param = []) {
        $item_name = $param['item_name'] ?? null;
        $qty = $param['qty'] ?? 0;
        $param['total_item'] = $qty;
        $imposition_name = $param['charge_in'];

        $itemExist = Item::validateByName($item_name);
        if($itemExist) {
            $item = Item::showByName($item_name);
            if($item) {
                $param['item_id'] = $item->id;
                $wrd = DB::table('warehouse_receipt_details')
                ->whereItemId($item->id)
                ->first();
                if($wrd) {
                    $param['rack_id'] = $wrd->rack_id;
                    $param['long'] = $wrd->long;
                    $param['wide'] = $wrd->wide;
                    $param['high'] = $wrd->high;
                    $param['weight'] = $wrd->weight;
                    $param['total_tonase'] = $param['weight'] * $qty;
                    $volume = Math::countVolume($param['long'], $param['wide'], $param['high']);
                    $param['total_volume'] = $volume * $qty;
                    $param['warehouse_receipt_detail_id'] = $wrd->id;
                    $param['total_tonase'] = $param['weight'] * $qty;
                    if($wrd->piece_id) {
                        $param['piece_id'] = $wrd->piece_id;
                    } else {
                        $param['piece_id'] = $item->piece_id;
                    }
                }
                if($param['piece_id']) {
                    $piece = Unit::show($param['piece_id']);
                    $param['piece_name'] = $piece->name;
                }
            }
        } else {
            throw new Exception($item_name . ' not found in stocklist. Please check stocklist');
        }

        $impositionExist = Imposition::validateByName($imposition_name);
        if(!$impositionExist) {
            throw new Exception($imposition_name . ' is not valid charge in / imposition');
        }
        $imposition = Imposition::showByName($imposition_name);
        if($imposition) {
            $param['imposition'] = $imposition->id;
        }
        

        return $param;
    }

    /*
      Date : 05-03-2021
      Description : Menempatkan barang pada bin location
      Developer : Didin
      Status : Create
    */
    public static function setSuggestionRack($params = [], $warehouse_id) {
        $racks = Bin::index(['warehouse_id' => $warehouse_id]);
        if(count($racks) > 0) {
            $rack_id = $racks[0]->id;
            foreach ($params as $i => $param) {
                $rack = Bin::show($rack_id);
                $rack_name = $rack->name;
                $params[$i]['rack_id'] = $rack_id;
                $params[$i]['rack_name'] = $rack_name;
                $params[$i]['storage_type'] = 'RACK';
            }
        }

        return $params;
    }

    public static function updatePrice($id, $price, $imposition) {
        $dt = self::show($id);
        switch($imposition) {
            case 1 :
                $unit = $dt->volume;
                break;
            case 2 :
                $unit = $dt->weight;
                break;
            case 3 :
                $unit = $dt->qty;
                break;
        }

        $total_price = $unit * $price;
        DB::table(self::$table)
        ->whereId($id) 
        ->update([
            'price' => $price,
            'total_price' => $total_price
        ]);
    }

    public static function updateTransportedQty($id, $qty) {
        self::validate($id);
        DB::table(self::$table)
        ->whereId($id)
        ->update([
            'transported' => $qty
        ]);
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table)
        ->where(self::$table . '.id', $id)
        ->first();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 05-03-2021
      Description : Menghubungkan job order dengan stok
      Developer : Didin
      Status : Create
    */
    public static function resetRequestOutbound($job_order_id) {
        self::clearStock($job_order_id);
        self::doRequestOutbound($job_order_id);
    }

    /*
      Date : 05-03-2021
      Description : Menghubungkan job order dengan stok
      Developer : Didin
      Status : Create
    */
    public static function doRequestOutbound($job_order_id) {
        $jo = JobOrder::show($job_order_id);
        $dt = DB::table(self::$table);
        $dt = $dt->whereNotNull('warehouse_receipt_detail_id');
        $dt = $dt->whereNull('requested_stock_transaction_id');
        if($job_order_id) {
            $dt = $dt->where(self::$table . '.header_id', $job_order_id);
        }

        $dt = $dt->get();
        $dt->each(function($x) use ($jo) {
            $stock = [];
            $stock['description'] = 'Telah direncanakan pengeluaran barang untuk job order ' . $x->item_name . ' pada transaksi ' . $jo->code;
            $stock['date_transaction'] = $jo->shipment_date;
            $stock['qty_keluar'] = $x->qty;
            $stock['warehouse_receipt_detail_id'] = $x->warehouse_receipt_detail_id;
            $stock['type_transaction'] = 'jobOrder';

            $stockTransaction = ST2::indexByWarehouseReceiptDetail($x->warehouse_receipt_detail_id);
            if(count($stockTransaction) > 0) {
                $rack_id = $stockTransaction[0]->rack_id;
                $stock['rack_id'] = $rack_id;
                $check_item = true;
                $request_stock_transaction_id = ST2::doRequestOutbound($stock, $check_item);
                DB::table(self::$table)->where(self::$table . '.id', $x->id)->update([
                    'requested_stock_transaction_id' => $request_stock_transaction_id
                ]);
            }
        });
    }

    public static function clear($job_order_id) {
        DB::table(self::$table)->whereHeaderId($job_order_id)->delete();
    }

    /*
      Date : 14-03-2021
      Description : Menghapus stok
      Developer : Didin
      Status : Create
    */
    public static function clearStock($job_order_id) {
        $items = self::index($job_order_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($job_order_id)->update([
            'requested_stock_transaction_id' => null
        ]);
        ST2::destroyMultiple($items);
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        $dt = self::show($id);
        $jo = JobOrder::show($dt->header_id);        

        $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
        $workOrder->storeItemPacketPrice($jo->work_order_id);
        self::clearStock($jo->id);

        DB::table(self::$table)
        ->whereId($id)
        ->delete();

        self::doRequestOutbound($jo->id);
    }

    public static function getTransported($id) {
        $qty = DB::table('manifest_details')
        ->whereJobOrderDetailId($id)
        ->sum('transported');

        return $qty;
    }

    public static  function getAvailableQty($id) {
        $dt = self::show($id);
        $transported = self::getTransported($id);
        $available = $dt->qty - $transported;

        return $available;
    }
}

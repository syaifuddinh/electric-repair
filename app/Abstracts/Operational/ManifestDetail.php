<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\ManifestDetail AS MD;
use App\Model\JobOrderDetail;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Inventory\PickingDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Operational\ManifestFTLController;
use App\Jobs\HitungJoCostManifestJob;
use App\Abstracts\Inventory\WarehouseStockDetail;
use App\Abstracts\Setting\Checker;
use App\Abstracts\JobOrderDetail AS JOD;
use App\Abstracts\Inventory\StockTransaction;

class ManifestDetail
{
    protected static $table = 'manifest_details';
    protected static $type_transaction = 'manifest';

    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('manifests', 'manifests.id', 'manifest_details.header_id');
        $dt = $dt->leftJoin('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id');
        $dt = $dt->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id');
        $dt = $dt->leftJoin('job_orders', 'job_orders.id', 'job_order_details.header_id');
        $dt = $dt->leftJoin('sales_orders', 'sales_orders.job_order_id', 'job_orders.id');
        $dt = $dt->leftJoin('contacts AS receivers', 'receivers.id', 'job_orders.receiver_id');
        $dt = $dt->leftJoin('contacts AS customers', 'customers.id', 'job_orders.customer_id');

        $request = self::fetchFilter($params);

        if($request['header_id']) {
            $dt = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        if($request['job_order_detail_id']) {
            $dt = $dt->where(self::$table . '.job_order_detail_id', $request['job_order_detail_id']);
        }

        if($request['job_order_id']) {
            $dt = $dt->where('job_order_details.header_id', $request['job_order_id']);
        }

        if($request['delivery_order_id']) {
            $deliveryOrder = DB::table('delivery_order_drivers')
            ->whereRaw('id = ' . $request['delivery_order_id'])
            ->select('manifest_id');
            $deliveryOrder = $deliveryOrder->toSql();
            $dt = $dt->whereRaw(self::$table . ".header_id IN ($deliveryOrder)");
        }

        return $dt;
    }

    public static function getTransportedQtyTotal($id) {
        $dt = self::show($id);
        $r = 0;
        if($dt->job_order_detail_id) {
            $items = self::query(['job_order_detail_id' => $dt->job_order_detail_id]);
            $r = $items->sum(self::$table . '.transported');
        }   

        return $r;
    }

    /*
      Date : 14-03-2021
      Description : Update riwayat pengeluaran stok pada item manifest
      Developer : Didin
      Status : Create
    */
    public static function updateOutbound($stock_transaction_id, $id) {
        if($stock_transaction_id) {
            StockTransaction::validate($stock_transaction_id);
        }
        self::validate($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'outbound_stock_transaction_id' => $stock_transaction_id
        ]);
    }

    /*
      Date : 14-03-2021
      Description : Membuat pengeluaran barang untuk 1 barang
      Developer : Didin
      Status : Create
    */
    public static function doOutbound($id) {
        $dt = self::show($id);
        if($dt->transported > 0) {

            if($dt->outbound_stock_transaction_id) {
                self::updateOutbound(null, $id);
                StockTransaction::destroy($dt->outbound_stock_transaction_id);
            }
            $header = Manifest::show($dt->header_id);
            $params = [];
            $params['qty_keluar'] = $dt->transported;
            $params['date_transaction'] = Carbon::now();
            $params['description'] = 'Muat Barang pada manifest - #' . $header->code;
            $params['rack_id'] = $dt->rack_id;
            $params['warehouse_receipt_detail_id'] = $dt->warehouse_receipt_detail_id;
            $params['item_id'] = $dt->item_id;
            $params['type_transaction'] = self::$type_transaction;
            $stock_transaction_id = StockTransaction::doOutbound($params);
            self::updateOutbound($stock_transaction_id, $id);
        }

    }

    public static function updateTransportedQtyTotal($id) {
        $dt = self::show($id);
        if($dt->job_order_detail_id) {
            $qty = self::getTransportedQtyTotal($id);
            JOD::updateTransportedQty($dt->job_order_detail_id, $qty);
        }   
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['header_id'] = $args['header_id'] ?? null;
        $params['job_order_id'] = $args['job_order_id'] ?? null;
        $params['job_order_detail_id'] = $args['job_order_detail_id'] ?? null;
        $params['delivery_order_id'] = $args['delivery_order_id'] ?? null;

        return $params;
    }

    /*
      Date : 14-09-2020
      Description : Menampilkan daftar item manifest
      Developer : Didin
      Status : Create
    */
    public static function index($manifest_id) {
        Manifest::validate($manifest_id);
        $jobOrderStock = WarehouseStockDetail::query()
        ->join('job_order_details', 'job_order_details.warehouse_receipt_detail_id', 'warehouse_stock_details.warehouse_receipt_detail_id')
        ->select('job_order_details.id', DB::raw('SUM(warehouse_stock_details.qty) AS stock'))
        ->groupBy('job_order_details.id');

        $pickingStock = WarehouseStockDetail::query()
        ->join('picking_details', 'picking_details.warehouse_receipt_detail_id', 'warehouse_stock_details.warehouse_receipt_detail_id')
        ->select('picking_details.id', DB::raw('SUM(warehouse_stock_details.qty) AS stock'))
        ->groupBy('picking_details.id');

        $dt = MD::with('job_order_detail.job_order.customer','job_order_detail.job_order.receiver')
        ->leftJoin('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id')
        ->leftJoin('job_orders', 'job_orders.id', 'job_order_details.header_id')
        ->leftJoin('contacts AS customers', 'job_orders.customer_id', 'customers.id')
        ->leftJoin('sales_orders', 'job_orders.id', 'sales_orders.job_order_id')
        ->leftJoin('picking_details', 'picking_details.id', 'manifest_details.picking_detail_id')
        ->leftJoin('items', 'items.id', 'picking_details.item_id')
        ->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id')
        ->leftJoin('pieces AS item_pieces', 'item_pieces.id', 'items.piece_id')
        ->leftJoinSub($jobOrderStock, 'job_order_stocks', function($join){
            $join->on('job_order_stocks.id', 'job_order_details.id');
        })
        ->leftJoinSub($pickingStock, 'picking_stocks', function($join){
            $join->on('picking_stocks.id', 'picking_details.id');
        })
        ->where('manifest_details.header_id', $manifest_id)
        ->selectRaw('
              manifest_details.*, 
              customers.name AS customer_name,
              customers.address AS customer_address,
              COALESCE(sales_orders.code, job_orders.code) AS `code`,
              COALESCE(job_order_details.item_name, items.name) AS item_name,
              COALESCE(pieces.name, item_pieces.name) as satuan, 
              (manifest_details.discharged_qty - manifest_details.transported) as qty_selisih,
              IF(
                manifest_details.job_order_detail_id IS NOT NULL,
                manifest_details.transported /
                job_order_details.qty * 
                job_order_details.weight,
                manifest_details.transported * items.tonase
              ) as tonase, 
              (
                manifest_details.transported /
                job_order_details.qty *
                job_order_details.volume
              ) as volume, 
              IFNULL(picking_stocks.stock, job_order_stocks.stock) AS stock')
        ->get();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['job_order_detail_id'] = $args['job_order_detail_id'] ?? null;
        $params['transported'] = $args['transported'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;
        $params['update_by'] = $args['update_by'] ?? null;

        $warehouse_receipt_detail_id = null;

        if($params['job_order_detail_id']) {
            $jod = JOD::show($params['job_order_detail_id']);
            $warehouse_receipt_detail_id = $jod->warehouse_receipt_detail_id;   
        }

        $params['warehouse_receipt_detail_id'] = $jod->warehouse_stock_detail_id;

        return $params;
    }

    public static function storeMultiple($params, $create_by) {
        if(is_array($params)) {
            foreach ($params as $param) {
                $param['create_by'] = $create_by;
                $param['update_by'] = $create_by;
                self::store($param);
            }
        }
    }


    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        $dt = $dt->first();
        if(!$dt) {
            throw new Exception('Manifest detail not found');
        }
    }

    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(
            self::$table . '.*', 
            'job_order_details.item_id',
            'job_order_details.warehouse_receipt_detail_id',
            'job_order_details.rack_id'
        );

        $dt = $dt->first();

        return $dt;
    }

    public static function store(Request $request, $id) {
        Manifest::validate($id);
        $m = new ManifestFTLController();
        if(!is_array($request)) {
            if($request->picking_detail_id) {
                PickingDetail::validate($request->picking_detail_id);
            }
            $params = ['detail' => [$request->all()]];
            $params = new Request($params);
        } else {
            $params = $request;
        }
        $m->add_item($params, $id);
    }

    public static function destroy($id) {
        self::validate($id);
        
        DB::beginTransaction();
        $m=MD::find($id);
        $m_header = $m->header_id;
        JobOrderDetail::where('id', $m->job_order_detail_id)->update([
        'transported' => DB::raw('transported-'.$m->transported),
        'leftover' => DB::raw('leftover+'.$m->transported),
        ]);
        $m->delete();
        DB::commit();

        HitungJoCostManifestJob::dispatch($m_header);
    }

    /*
      Date : 14-09-2020
      Description : Hapus item manifest bersamaan
      Developer : Didin
      Status : Create
    */
    public static function clear($job_order_id = null) {
        $dt = DB::table(self::$table);
        if($job_order_id) {
            $jod = DB::table('job_order_details')->select('job_order_details.id');
            $jod = $jod->whereRaw("job_order_details.header_id = $job_order_id");
            $jodStr = $jod->toSql();
            $dt = $dt->whereRaw("job_order_detail_id IN ($jodStr)");
        }

        $dt->delete();
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang terangkut
      Developer : Didin
      Status : Create
    */
    public static function updateTransportedQty($id, $qty) {
        self::validate($id);
        Checker::validateIsZero($qty);
        DB::table(self::$table)->whereId($id)->update([
            'transported' => $qty
        ]);
        self::doOutbound($id);
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang dibongkar
      Developer : Didin
      Status : Create
    */
    public static function updateDischargedQty($id, $qty) {
        $dt = self::show($id);
        Checker::validateIsZero($qty);
        if($qty > $dt->transported) {
            throw new Exception('Qty bongkar tidak boleh lebih besar dari qty muat');
        }
        DB::table(self::$table)->whereId($id)->update([
            'discharged_qty' => $qty
        ]);
    }


}

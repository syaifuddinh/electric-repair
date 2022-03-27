<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\Picking;
use App\Abstracts\Inventory\PickingDetail;

class Picking
{
    protected static $table = 'pickings';

    /*
      Date : 29-08-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->leftJoin('companies', 'companies.id', self::$table . '.company_id');
        $dt = $dt->leftJoin('warehouses', 'warehouses.id', self::$table . '.warehouse_id');
        $dt = $dt->leftJoin('picking_statuses', 'picking_statuses.id', self::$table . '.status');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(
            self::$table . '.*', 
            'warehouses.name AS warehouse_name', 
            'companies.name AS company_name', 
            'picking_statuses.name AS status_name', 
            'picking_statuses.slug AS status_slug'
        );
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi picking detail
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Picking not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['company_id'] = $args['company_id'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;

        if(!$params['company_id']) {
            throw new Exception('Branch is required');
        }

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
        }

        if(!$params['date_transaction']) {
            throw new Exception('Date transaction is required');
        } else {
            $params['date_transaction'] = Carbon::parse($params['date_transaction']);
        }

        return $params;
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
        $detail = $params['detail'] ?? [];
        $params = self::fetch($params);
        $code = new TransactionCode($params['company_id'], 'picking');
        $code->setCode();
        $trx_code = $code->getCode();
        $params['code'] = $trx_code;
        $id = DB::table(self::$table)
        ->insertGetId($params);

        PickingDetail::storeMultiple($detail, $id);
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        self::validate($id);
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            PickingDetail::clearStock($id);
            PickingDetail::clear($id);

            PickingDetail::storeMultiple($detail, $id);
        }
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya disetujui
      Developer : Didin
      Status : Create
    */
    public static function getApproveStatus() {
        return 2;
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $approveStatus = self::getApproveStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 23-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        PickingDetail::clear($id);
        DB::table(self::$table)->whereId($id)->delete();
    }
}

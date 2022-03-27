<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\ItemDeletionDetail;
use App\Abstracts\Inventory\ItemDeletionStatus;
use App\Abstracts\Setting\Checker;

class ItemDeletion
{
    protected static $table = 'item_deletions';
    public static $type_transaction = 'itemDeletion';

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
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('warehouses', 'warehouses.id', self::$table . '.warehouse_id');
        $dt = $dt->leftJoin('companies', 'companies.id', 'warehouses.company_id');
        $dt = $dt->leftJoin('item_deletion_statuses', 'item_deletions.status', 'item_deletion_statuses.id');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*', 'warehouses.name AS warehouse_name', 'companies.name AS company_name', 'item_deletion_statuses.name AS status_name');
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
            throw new Exception('ItemDeletion detail not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params = [];
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        Checker::checkDate($params['date_transaction']);
        $params['date_transaction'] = Carbon::parse($params['date_transaction'])->format('Y-m-d');
        $params['description'] = $args['description'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
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
        $wh = Warehouse::show($params['warehouse_id']);
        $company_id = $wh->company_id;
        $code = new TransactionCode($company_id, self::$type_transaction);
        $code->setCode();
        $trx_code = $code->getCode();
        $params['code'] = $trx_code;
        $params['status'] = ItemDeletionStatus::getDraft();
        $id = DB::table(self::$table)
        ->insertGetId($params);

        ItemDeletionDetail::storeMultiple($detail, $id);
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        self::validateIsApproved($id);
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            ItemDeletionDetail::clearStock($id);
            ItemDeletionDetail::clear($id);

            ItemDeletionDetail::storeMultiple($detail, $id);
        }
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $approveStatus = ItemDeletionStatus::getApproved();
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
        ItemDeletionDetail::clearStock($id);
        ItemDeletionDetail::clear($id);
        DB::table(self::$table)->whereId($id)->delete();
    }

    /*
      Date : 29-08-2021
      Description : Membuat pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function approve($approve_by, $id) {
        self::validateIsApproved($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => ItemDeletionStatus::getApproved(),
            'approve_by' => $approve_by,
            'date_approve' => Carbon::now()
        ]);

        ItemDeletionDetail::doMultipleOutbound($id);
    }
}

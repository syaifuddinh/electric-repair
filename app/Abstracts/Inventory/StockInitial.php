<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Setting\TypeTransaction;
use App\Abstracts\Setting\Finance\AccountDefault;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\ReceiptType;
use App\Abstracts\WarehouseReceipt;

class StockInitial 
{
    protected static $table = 'stock_initials';

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        Checker::checkDate($params['date_transaction']);
        $params['date_transaction'] = Carbon::parse($params['date_transaction'])->format('Y-m-d');
        $params['description'] = $args['description'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;
        $params['item_id'] = $args['item_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['price'] = $args['price'] ?? 0;
        $params['total'] = $params['qty'] * $params['price'];

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
        } else {
            $wh = Warehouse::show($params['warehouse_id']);
            $params['company_id']= $wh->company_id;
        }

        return $params;
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
            throw new Exception('Putaway not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Validasi data
      Developer : Didin
      Status : Create
    */
    public static function validateIsExist($item_id, $warehouse_id) {
        $params = [];
        $params['warehouse_id'] = $warehouse_id;
        $params['item_id'] = $item_id;
        $qty = StockTransaction::cekStok($params);
        if($qty > 0) {
            throw new Exception('Stock was available');
        }
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);

        self::validateIsExist($insert['item_id'], $insert['warehouse_id']);
        self::validateAccountItem($insert['item_id']);
        AccountDefault::validateAccountSaldoAwal();

        $company_id = $insert['company_id'];
        $code = new TransactionCode($company_id, 'stockInitial');
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['created_at'] = Carbon::now();
        $insert['code'] = $trx_code;
        $insert['create_by'] = $insert['create_by'] ?? auth()->id();
        $id = DB::table(self::$table)->insertGetId($insert);

        self::storeJournal($id);
        self::doInbound($id);

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validate($id);
        $update = self::fetch($params);
        DB::table(self::$table)
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 29-08-2021
      Description : Validasi apakah akun persediaan pada barang sudah ditentukan
      Developer : Didin
      Status : Create
    */
    public static function validateAccountItem($item_id) {
        $item = Item::show($item_id);
        if(!$item->account_id) {
            throw new Exception('Akun persediaan untuk item ' . $item->name . ' belum terdata. Silahkan entry di menu master item');
        }
    }

    public static function storeJournal($id) {
        $dt = self::show($id);
        $item = Item::show($dt->item_id);
        $tp = TypeTransaction::showBySlug('saldoAwal');
        $account = AccountDefault::show();

        $params['type_transaction_id'] = $tp->id;
        $params['company_id'] = $dt->company_id;
        $params['date_transaction'] = $dt->date_transaction;
        $params['created_by'] = $dt->create_by;
        $params['code'] = $dt->code;
        $params['description'] = 'Persediaan Awal Barang';
        $journal_id = DB::table('journals')->insertGetid($params);

        $params = [];
        $params['header_id'] = $journal_id;
        $params['account_id'] = $item->account_id;
        $params['debet'] = $dt->total;
        DB::table('journal_details')->insert($params);

        $params = [];
        $params['header_id'] = $journal_id;
        $params['account_id'] = $account->saldo_awal;
        $params['credit'] = $dt->total;
        DB::table('journal_details')->insert($params);
    }

    public static function doInbound($id) {
        $dt = self::show($id);
        $item = Item::show($dt->item_id);
        $receiptType = ReceiptType::showByCode('r09');
        if(!$receiptType) {
            throw new Exception('Receipt type for saldo awal is not exist');
        }
        $params = [];
        $params['status'] = 1;
        $params['receive_date'] = Carbon::now()->format('d-m-Y');
        $params['receive_time'] = Carbon::now()->format('H:i');
        $params['company_id'] = $dt->company_id;
        $params['warehouse_id'] = $dt->warehouse_id;
        $params['receipt_type_id'] = $receiptType->id;
        $params['warehouse_staff_id'] = $dt->create_by;

        $detail = [];
        $detail['storage_type'] = 'HANDLING';
        $detail['imposition'] = 3;
        $detail['item_id'] = $dt->item_id;
        $detail['item_name'] = $item->name;
        $detail['qty'] = $dt->qty;
        $detail['long'] = $item->long;
        $detail['wide'] = $item->wide;
        $detail['high'] = $item->height;
        $detail['weight'] = $item->tonase;
        $detail = (object) $detail;
        $params['detail'] = [ $detail ];

        WarehouseReceipt::store($params);
    }
}

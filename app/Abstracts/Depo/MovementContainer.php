<?php

namespace App\Abstracts\Depo;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Depo\MovementContainerStatus;
use App\Abstracts\Depo\MovementContainerDetail;

class MovementContainer 
{
    protected static $table = 'movement_containers';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('contacts', 'contacts.id', 'operator_id');
        $dt = $dt->leftJoin('companies', 'companies.id', self::$table . '.company_id');
        $dt = $dt->leftJoin('movement_container_statuses', 'movement_container_statuses.id', self::$table . '.status');

        $params = self::fetchFilter($params);
        $dt = self::filterQuery($params, $dt);

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
        $params['created_by'] = $args['created_by'] ?? auth()->id();
        $params['company_id'] = $args['company_id'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['operator_id'] = $args['operator_id'] ?? null;
        $params['date'] = $args['date'] ?? null;
        self::validateInput($params);

        $params['date'] = Carbon::parse($params['date'])->format('Y-m-d H:i:s');
        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Validasi input data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params['company_id'] = $args['company_id'] ?? null;
        $params['start_date'] = $args['start_date'] ?? null;
        $params['end_date'] = $args['end_date'] ?? null;

        if($params['start_date']) {
            $params['start_date'] = Carbon::parse($params['start_date'])->format('Y-m-d');
        }

        if($params['end_date']) {
            $params['end_date'] = Carbon::parse($params['end_date'])->format('Y-m-d');
        }

        return $params;
    }

    public static function filterQuery($params, $dt) {
        if($params['company_id']) {
            $dt = $dt->where(self::$table . '.company_id', $params['company_id']);
        }

        if($params['start_date']) {
            $dt = $dt->where(self::$table . '.date', '>=', $params['start_date']);
        }

        if($params['end_date']) {
            $dt = $dt->where(self::$table . '.date', '<=', $params['end_date']);
        }

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Validasi input data
      Developer : Didin
      Status : Create
    */
    public static function validateInput($params) {
        $r = new Request($params);
        $r->validate([
            'operator_id' => 'required',
            'company_id' => 'required',
            'date' => 'required'
        ]);
        Checker::checkDate($params['date']);
        Checker::checkCompany($params['company_id']);
        Checker::checkContact($params['operator_id']);
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
            throw new Exception('Item condition not found');
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
        $dt = self::query();
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.id', self::$table . '.date', self::$table . '.code', 'contacts.name AS operator_name',  'companies.name AS company_name', 'movement_container_statuses.name AS status_name', self::$table . '.status', self::$table . '.description', self::$table . '.operator_id', self::$table . '.status');

        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);
        $code = new TransactionCode($params['company_id'], 'movementContainer');
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['code'] = $trx_code;
        $insert['status'] = MovementContainerStatus::getDraft();
        $insert['created_at'] = Carbon::now();
        $id = DB::table(self::$table)
        ->insertGetId($insert);

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function validateApproved($id) {
        $dt = self::show($id);
        $status = MovementContainerStatus::getApproved();
        if($dt->status == $status) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validateApproved($id);
        $update = self::fetch($params);
        DB::table(self::$table)
        ->whereId($id)
        ->update($update);
    }

    /*
      Date : 29-08-2021
      Description : Approve data
      Developer : Didin
      Status : Create
    */
    public static function approve($id) {
        self::validateApproved($id);
        $update = [];
        $update['status'] = MovementContainerStatus::getApproved();
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
        self::validateApproved($id);
        MovementContainerDetail::clear($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }
}

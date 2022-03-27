<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Finance\PayableDetail;

class Payable 
{
    protected static $table = 'payables';

    /*
      Date : 22-06-2021
      Description : Menangkap parameter untuk filter data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params["status"] = $args["status"] ?? null;
        $params["contact_id"] = $args["contact_id"] ?? null;
        $params["company_id"] = $args["company_id"] ?? null;
        $params["start_date"] = $args["start_date"] ?? null;
        $params["end_date"] = $args["end_date"] ?? null;
        $params["start_due_date"] = $args["start_due_date"] ?? null;
        $params["end_due_date"] = $args["end_due_date"] ?? null;
        $params["status"] = $args["status"] ?? null;

        return $params;
    }

    /*
      Date : 22-06-2021
      Description : Menangkap parameter untuk filter data
      Developer : Didin
      Status : Create
    */
    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $hutang = DB::table("payables")
        ->leftJoin('companies', 'companies.id', 'payables.company_id')
        ->leftJoin('contacts', 'contacts.id', 'payables.contact_id')
        ->leftJoin("journals", 'journals.id', 'payables.journal_id')
        ->where('journals.status', 3)
        ->select(
            'payables.id',
            'payables.code',
            'payables.created_at',
            'contacts.name AS contact_name',
            'companies.name AS company_name',
            'payables.date_transaction',
            'payables.date_tempo',
            DB::raw("DATEDIFF(NOW(), payables.date_tempo) AS umur"),
            'payables.credit AS sisa',
            'payables.credit AS sisa_hutang',
            DB::raw("CASE WHEN payables.credit = 0 THEN 1 WHEN DATEDIFF(NOW(), payables.date_tempo) > 0 THEN 2 ELSE 3 END AS status")
        );

        if(auth()->user()->is_admin==0)
            $hutang->where('company_id', auth()->user()->company_id);

        if($request['contact_id'])
            $hutang->where('contact_id', $request['contact_id']);

        if($request['start_date'])
            $hutang->where('date_transaction', '>=', preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $request['start_date']));

        if($request['end_date'])
            $hutang->where('date_transaction', '<=', preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $request['end_date']));

        if($request['start_due_date'])
            $hutang->where('date_tempo', '>=', preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $request['start_due_date']));

        if($request['end_due_date'])
            $hutang->where('date_tempo', '<=', preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $request['end_due_date']));

        $hutang = $hutang->orderByRaw('payables.date_transaction DESC, payables.id DESC');

        $hutang = DB::query()->fromSub($hutang, 'payables');

        if($request['status']) {
            $hutang = $hutang->where('status', $request['status']);
        }

        return $hutang;
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
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        
        $dt = DB::table(self::$table);
        $dt = $dt->whereId($id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        PayableDetail::clear();
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }
    
    /*
      Date : 14-03-2021
      Description : Mendapatkan sisa hutang
      Developer : Didin
      Status : Create
    */
    public static function getSisa($params = []) {
        $raw = self::query($params);
        $r = $raw->sum(DB::raw("sisa"));

        return $r;
    }
}

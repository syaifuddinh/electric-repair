<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class Receivable 
{
    protected static $table = 'receivables';

    /*
      Date : 22-06-2021
      Description : Menangkap parameter untuk filter data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params["status"] = $args["status"] ?? null;
        $params["customer_id"] = $args["customer_id"] ?? null;
        $params["start_date_invoice"] = $args["start_date_invoice"] ?? null;
        $params["end_date_invoice"] = $args["end_date_invoice"] ?? null;
        $params["start_due_date"] = $args["start_due_date"] ?? null;
        $params["end_due_date"] = $args["end_due_date"] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Menangkap parameter input filter data
      Developer : Didin
      Status : Create
    */
    public static function query($request = []) {
        $request = self::fetchFilter($request);

        $wr="(receivables.type_transaction_id = 26 OR receivables.type_transaction_id = 2) ";
        if ($request['customer_id']) {
            $wr.=" and receivables.contact_id = " . 

            $request['customer_id'];
        }

        $item=DB::table('receivables')
        ->leftJoin('invoices','invoices.id','receivables.relation_id')
        ->leftJoin(DB::raw('(select group_concat(distinct aju_number) as aju, group_concat(distinct no_bl) as no_bl, invoice_id from job_orders group by invoice_id) as i'),'i.invoice_id','invoices.id')
        ->leftJoin('contacts','contacts.id','receivables.contact_id')
        ->whereRaw($wr)
        ->selectRaw('
            receivables.*,
            invoices.code as code_invoice,
            invoices.id as invoice_id,
            i.aju,
            i.no_bl,
            (receivables.debet-receivables.credit) as sisa,
            contacts.name as customer,
            datediff(date(now()),receivables.date_tempo) as umur,
            if( receivables.debet-receivables.credit<=0,1,if(datediff(date(now()),receivables.date_tempo)>0,2,3) ) as status_piutang
            ')->groupBy('receivables.id');

        if ($request['start_date_invoice']) {
            $start=Carbon::parse($request['start_date_invoice'])->format('Y-m-d');
            $item = $item->where("receivables.date_transaction" , ">=", $start);
        }

        if ($request['end_date_invoice']) {
            $end=Carbon::parse($request['end_date_invoice'])->format('Y-m-d');
            $item = $item->where("receivables.date_transaction" , "<=", $end);
        }

        if ($request['start_due_date']) {
            $start=Carbon::parse($request['start_due_date'])->format('Y-m-d');
            $item = $item->where("receivables.date_tempo" , ">=", $start);
        }

        if ($request['end_due_date']) {
            $end=Carbon::parse($request['end_due_date'])->format('Y-m-d');
            $item = $item->where("receivables.date_tempo" , "<=", $end);
        }

        if(isset($request['status'])){
            $item->havingRaw("status_piutang = {$request['status']}");
        }

        return $item;
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
      Description : Mendapatkan total sisa piutang
      Developer : Didin
      Status : Create
    */
    public static function getSisa($params = []) {
        $raw = self::query($params);
        $dt = DB::query()->fromSub($raw, "raw");
        $r = $dt->sum(DB::raw("IF(status_piutang = 1, 0, sisa)"));
        $r = round($r);

        return $r;
    }
}

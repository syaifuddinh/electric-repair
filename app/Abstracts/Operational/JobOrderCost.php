<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use App\Abstracts\JobOrder;
use App\Abstracts\Contact;
use App\Abstracts\Setting\Operational\CostType;
use App\Abstracts\Setting\Math;
use Exception;
use Illuminate\Http\Request;

class JobOrderCost
{
    protected static $table = 'job_order_costs';

    /*
      Date : 12-02-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('cost_types', 'cost_types.id', self::$table . '.cost_type_id');
        $dt = $dt->leftJoin('contacts', 'contacts.id', self::$table . '.vendor_id');

        if($request['header_id']) {
            $dt = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        $dt = $dt->select(self::$table . '.*', 'cost_types.name AS cost_type_name', 'contacts.name AS vendor_name', DB::raw('CASE WHEN ' . self::$table . '.type = 1 THEN "Operational" WHEN ' . self::$table . '.type = 2 THEN "Reimbursement" END AS type_name'));

        return $dt;
    }

    /*
      Date : 21-04-2021
      Description : Mendapatkan parameter filter data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($params = []) {
        $request = [];
        $request['header_id'] = $params['header_id'] ?? null;

        return $request;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Menampilkan detail job order cost
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->join('job_orders', 'job_orders.id', self::$table . '.header_id');
        $dt = $dt->join('cost_types', 'cost_types.id', 'job_order_costs.cost_type_id');
        $dt = $dt->where('job_order_costs.id', $id);
        $dt = $dt->select(
            'job_order_costs.*', 
            'cost_types.name',
            'cost_types.akun_biaya',
            'cost_types.akun_uang_muka',
            'cost_types.is_insurance', 
            'job_orders.company_id',
            'job_orders.code'
        );

        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga asuransi
      Developer : Didin
      Status : Create
    */
    public static function countPrice($cost_type_id, $job_order_id, $price = 0) {
        $ct = CostType::show($cost_type_id);
        if($ct->is_insurance == 1) {
            $jo = JobOrder::show($job_order_id);
            $percentage = $ct->percentage ?? 0;
            $price = Math::countPercent($percentage, $jo->total_price);
        }
        return $price;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung harga asuransi
      Developer : Didin
      Status : Create
    */
    public static function countTotalPrice($cost_type_id, $job_order_id, $price = 0, $qty = 0) {
        $ct = CostType::show($cost_type_id);
        $r = CostType::getTotalPrice($cost_type_id, $qty, $price);
        if($ct->is_insurance == 1) {
            $jo = JobOrder::show($job_order_id);
            $percentage = $ct->percentage ?? 0;
            $r = Math::countPercent($percentage, $jo->total_price);
        } else if($ct->is_shipment == 1) {
            if($ct->cost_route_type_slug == 'requested') {
                $qty = JobOrder::getRequestedDistance($job_order_id);
                $r = CostType::getTotalPrice($cost_type_id, $qty, $price);
            }
        } 
        return $r;
    }

    public static function storeRequestedDistance($id) {
        $dt = self::show($id);
        $ct = CostType::show($dt->cost_type_id);
        if($ct->is_shipment == 1 && $ct->cost_route_type_slug == 'requested') {
            $qty = JobOrder::getRequestedDistance($dt->header_id); 
            DB::table(self::$table)
            ->whereId($id)
            ->update([
                'qty' => $qty
            ]);
        }

    }

    public static function storeVendorJob($id) {
        $status = DB::table('vendor_job_statuses')
        ->whereSlug('draft')
        ->first();
        if($status) {
            DB::table('job_order_costs')
            ->whereId($id)
            ->update([
                'vendor_job_status_id' => $status->id
            ]);
        }
    }

    public static function getPostedStatus() {
        return 5;
    }

    /*
      Date : 05-03-2021
      Description : Membuat jurnal dan hutang untuk job order cost
      Developer : Didin
      Status : Create
    */
    public function generateJournal($id) {
        $dt = self::show($id);

        $journal_id = DB::table("journals")->insertGetId([
            'company_id' => $dt->company_id,
            'type_transaction_id' => 50,
            'date_transaction' => Carbon::now(),
            'created_by' => auth()->id(),
            'code' => $dt->code,
            'status' => 2,
            'description' => "Biaya JO - $dt->code - {$dt->name}",
            'debet' => 0,
            'credit' => 0,
        ]);
            DB::table('journal_details')->insertGetId([
              'header_id' => $journal_id,
              'account_id' => $dt->akun_biaya,
              'debet' => $dt->total_price,
              'credit' => 0,
              'description' => "Biaya JO - $dt->name"
            ]);

            if (!$dt->akun_uang_muka) return response()->json(['message' => "Tidak ada akun uang muka / clearing account yang disetting pada biaya {$dt->name}"],500);

            DB::table('journal_details')->insertGetId([
              'header_id' => $journal_id,
              'account_id' => $dt->akun_uang_muka,
              'debet' => 0,
              'credit' => $dt->total_price,
              'description' => "Biaya JO - $dt->name"
            ]);

            $payable_id = DB::table('payables')->insertGetId([
                'company_id' => $jo->company_id,
                'contact_id' => $joc->vendor_id,
                'type_transaction_id' => 50,
                'journal_id' => $journal_id,
                'relation_id' => $value->id,
                'created_by' => Auth::id(),
                'code' => $jo->code,
                'date_transaction' => Carbon::now(),
                'date_tempo' => Carbon::now(),
                'description' => "Biaya JO - $jo->code - $value->name",
                'is_invoice' => 0
            ]);

            DB::table('payable_details')->insert([
                'header_id' => $payable_id,
                'journal_id' => $journal_id,
                'type_transaction_id' => 50,
                'relation_id' => $value->id,
                'code' => $jo->code,
                'date_transaction' => Carbon::now(),
                'credit' => $value->total_price,
                'description' => "Biaya JO - $dt->code - $dt->name",
                'is_journal' => 1
            ]);
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi limit hutang ketika input jo cost
      Developer : Didin
      Status : Create
    */
    public static function validasiLimitHutang($id) {
        $dt = self::show($id);
        $total_price = $dt->total_price;
        Contact::validasiLimitHutang($dt->vendor_id, $total_price);
    }
}

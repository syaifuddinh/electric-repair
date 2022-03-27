<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use App\Abstracts\Setting\Checker;
use App\Model\QuotationDetail AS QD;
use App\Abstracts\Setting\Imposition;

class QuotationFile
{
    protected static $table = 'quotation_files';

    /*
      Date : 12-02-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('quotation_details')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Quotation detail not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Menampilkan detail 
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table('quotation_details')
        ->join('services', 'services.id', 'quotation_details.service_id')
        ->where('quotation_details.id', $id)
        ->select('quotation_details.*', 'services.service_type_id')
        ->first();

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Mencari kolom harga berdasarkan pengenaan
      Developer : Didin
      Status : Create
    */
    public static function getPriceColumnByImposition($imposition) {
        $orderBy = null;
        Checker::checkImposition($imposition);

        switch($imposition) {
            case 1 :
                $orderBy = 'price_inquery_volume';
                break;
            case 2 :
                $orderBy = 'price_inquery_tonase';
                break;
            case 3 :
                $orderBy = 'price_inquery_item';
                break;
        }

        return $orderBy;
    }

    /*
      Date : 12-02-2021
      Description : Set harga termurah untuk harga dengan multiple minimum (untuk layanan penerbangan internasional)
      Developer : Didin
      Status : Create
    */
    public static function setMainPriceForMultipleMinimum($id) {
        $dt = self::show($id);
        if($dt->service_type_id == 1 && $dt->min_type == 2) {
            $smallestPrice = PriceListMinimumDetail::findSmallestPrice(null, $id, $dt->imposition);
            $priceColumn = self::getPriceColumnByImposition($dt->imposition);
            self::query()->whereId($id)->update([
                $priceColumn => $smallestPrice
            ]);
        }
    }

    /*
      Date : 28-05-2021
      Description : Index data quotation file
      Developer : Didin
      Status : Create
    */
    public static function index($header_id) {
        $dt = DB::table(self::$table)->whereHeaderId($header_id)->get();

        return $dt;
    }
}

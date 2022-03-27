<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Setting\Checker;

class PriceListMinimumDetail
{
    /*
      Date : 22-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('price_list_minimum_details');

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('price_list_minimum_details')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Menampilkan detail stuffing
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query()
        ->whereId($id)
        ->first();

        return $dt;
    }

    public static function getPriceColumnByImposition($imposition) {
        $orderBy = null;
        Checker::checkImposition($imposition);

        switch($imposition) {
            case 1 :
                $orderBy = 'price_per_m3';
                break;
            case 2 :
                $orderBy = 'price_per_kg';
                break;
            case 3 :
                $orderBy = 'price_per_item';
                break;
        }

        return $orderBy;
    }

    public static function getSizeColumnByImposition($imposition) {
        $orderBy = null;
        Checker::checkImposition($imposition);

        switch($imposition) {
            case 1 :
                $orderBy = 'min_m3';
                break;
            case 2 :
                $orderBy = 'min_kg';
                break;
            case 3 :
                $orderBy = 'min_item';
                break;
        }

        return $orderBy;
    }

    /*
      Date : 12-02-2021
      Description : Mencari harga termurah berdasarkan pengenaan harga
      Developer : Didin
      Status : Create
    */
    public static function findSmallestPrice($price_list_id = null, $quotation_detail_id = null, $imposition = 1) {
        $dt = self::query();
        $orderBy = self::getSizeColumnByImposition($imposition);
        $priceColumn = self::getPriceColumnByImposition($imposition);
        if($price_list_id) {
            Checker::checkPriceList($price_list_id);
            $dt = $dt->where('price_list_id', $price_list_id);
        } else if($quotation_detail_id) {
            Checker::checkQuotationDetail($quotation_detail_id);
            $dt = $dt->where('quotation_detail_id', $quotation_detail_id);
        }
        $dt = $dt->where($priceColumn, '>', 0);
        $dt = $dt->orderBy($orderBy, 'asc');
        $dt = (array )$dt->first();
        $dt = $dt[$priceColumn] ?? 0;

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Clone berdasarkan quotation detail
      Developer : Didin
      Status : Create
    */
    public static function cloneByQuotationDetail($old_quotation_detail_id, $new_quotation_detail_id) {
        $dt = self::query();
        $dt = $dt->where('quotation_detail_id', $old_quotation_detail_id);
        $dt = $dt->get()->toArray();
        foreach ($dt as $item) {
            $item = (array)$item;
            unset($item['id']);
            $item['quotation_detail_id'] = $new_quotation_detail_id;
            self::query()->insert($item);
        }
    }
}

<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\PriceListMinimumDetail;

class PriceList
{
    /*
      Date : 12-02-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('price_lists');

        return $dt;
    }
    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('price_lists')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Price list not found');
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
        $dt = DB::table('price_lists')
        ->join('services', 'services.id', 'price_lists.service_id')
        ->where('price_lists.id', $id)
        ->select('price_lists.*', 'services.service_type_id')
        ->first();

        return $dt;
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
            $smallestPriceVolume = PriceListMinimumDetail::findSmallestPrice($id, null, 1);
            $smallestPriceTonase = PriceListMinimumDetail::findSmallestPrice($id, null, 2);
            $smallestPriceItem = PriceListMinimumDetail::findSmallestPrice($id, null, 3);
            self::query()->whereId($id)->update([
                'price_volume' => $smallestPriceVolume,
                'price_tonase' => $smallestPriceTonase,
                'price_item' => $smallestPriceItem
            ]);
        }
    }
}

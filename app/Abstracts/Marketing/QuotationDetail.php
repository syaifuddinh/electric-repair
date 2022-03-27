<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use App\Abstracts\Setting\Checker;
use App\Model\QuotationDetail AS QD;
use App\Abstracts\Setting\Imposition;

class QuotationDetail
{
    protected static $table = 'quotation_details';

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
      Date : 05-03-2021
      Description : Index data quotation item
      Developer : Didin
      Status : Create
    */
    public static function index($header_id) {
        $dt = QD::with('commodity:id,name','service:id,name,service_type_id','piece:id,name','route','moda','vehicle_type:id,name','container_type')->where(self::$table . '.header_id', $header_id)->orderBy(self::$table . '.id','asc')->get();
        $dt = $dt->map(function($val){
            $penawaran = $val->price_inquery_full;
            if($val->service->service_type_id == 15) {
              $penawaran = $val->over_storage_price;
            } else if($val->service->service_type_id == 14) {
              $penawaran = $val->price_inquery_full;
            } else if(($val->service->service_type_id == 12 || $val->service->service_type_id == 13) && $val->handling_type == 1) {
              if($val->imposition == 1) {
                   $penawaran = $val->price_inquery_handling_volume;
              }
              else if($val->imposition == 2) {
                   $penawaran = $val->price_inquery_handling_tonase;
              }
              else if($val->imposition == 3) {
                   $penawaran = $val->price_inquery_item;
              }
              else {
                   $penawaran = $val->price_inquery_full;
              }
            } else if($val->service->service_type_id == 1) {
              if($val->imposition == 1) {
                   $penawaran = $val->price_inquery_volume;
              }
              else if($val->imposition == 2) {
                   $penawaran = $val->price_inquery_tonase;
              }
              else if($val->imposition == 3) {
                   $penawaran = $val->price_inquery_item;
              }
              else {
                   $penawaran = $val->price_inquery_full;
              }                
            }
            $val['price'] = $penawaran;

            return $val;
        });

        $dt = $dt->map(function($val){
            $val['charge_in'] = '';
            $service_type_id = $val->service->service_type_id;
            if($service_type_id == 6 || $service_type_id == 7) {
                $val['charge_in'] = $val->piece->name;
            } else if($service_type_id == 2) {
                $val['charge_in'] = 'Container';
            } else if($service_type_id == 3) {
                $val['charge_in'] = 'Unit';
            } else {
                if($val['imposition'] ?? null) {
                    $imposition = Imposition::show($val['imposition']);
                    $val['charge_in'] = $imposition->name;
                }
            }

            return $val;
        });

        return $dt;
    }
}

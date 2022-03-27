<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Marketing\PriceList;
use App\Abstracts\Marketing\QuotationDetail;

class WorkOrderDetail
{
    protected static $table = 'work_order_details';

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
      Description : Menampilkan detail work order detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table('work_order_details')
        ->whereId($id)
        ->first();

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Menampilkan detail job order cost
      Developer : Didin
      Status : Create
    */
    public static function getPriceInfo($id) {
        $dt = self::show($id);
        $res = [];

        if($dt->price_list_id) {
            $priceList = PriceList::show($dt->price_list_id);
            $res['service_type_id'] = $priceList->service_type_id;
            if($res['service_type_id'] == 15) {

                $res['price_item'] = $priceList->over_storage_price;
                $res['price_volume'] = $priceList->over_storage_price;
                $res['price_tonase'] = $priceList->over_storage_price;
            } else {
                $res['price_item'] = $priceList->price_item;
                $res['price_volume'] = $priceList->price_volume;
                $res['price_tonase'] = $priceList->price_tonase;
            }
            $res['price_full'] = $priceList->price_full;
            $res['over_storage_price'] = $priceList->over_storage_price;
            $res['min_volume'] = $priceList->min_volume;
            $res['min_tonase'] = $priceList->min_tonase;
            $res['min_item'] = $priceList->min_item;
            $res['free_storage_day'] = $priceList->free_storage_day;
            $res['handling_type'] = $priceList->handling_type;
            $res['container_type_id'] = $priceList->container_type_id;
        } else if ($dt->quotation_detail_id){
            $quotationDetail = QuotationDetail::show($dt->quotation_detail_id);
            $res['service_type_id'] = $quotationDetail->service_type_id;
            if($res['service_type_id'] == 15) {
                $res['price_item'] = $quotationDetail->over_storage_price;
                $res['price_volume'] = $quotationDetail->over_storage_price;
                $res['price_tonase'] = $quotationDetail->over_storage_price;
            } else {
                $res['price_item'] = $quotationDetail->price_inquery_item;
                $res['price_volume'] = $quotationDetail->price_inquery_volume;
                $res['price_tonase'] = $quotationDetail->price_inquery_handling_tonase;
            }
            $res['price_full'] = $quotationDetail->price_inquery_full;
            $res['over_storage_price'] = $quotationDetail->over_storage_price;
            $res['min_volume'] = $quotationDetail->price_inquery_min_volume;
            $res['min_tonase'] = $quotationDetail->price_inquery_min_tonase;
            $res['min_item'] = $quotationDetail->price_inquery_min_item;
            $res['free_storage_day'] = $quotationDetail->free_storage_day;
            $res['handling_type'] = $quotationDetail->handling_type;
            $res['container_type_id'] = $quotationDetail->container_type_id;

        }
        return $res;
    }

    public static function query() {
        $dt = DB::table('work_order_details')        
        ->leftJoin('price_lists', 'work_order_details.price_list_id', 'price_lists.id')
        ->leftJoin('services AS price_list_services', 'price_lists.service_id', 'price_list_services.id')
        ->leftJoin('quotation_details', 'work_order_details.quotation_detail_id', 'quotation_details.id')
        ->leftJoin('services AS quotation_detail_services', 'quotation_details.service_id', 'quotation_detail_services.id')
        ->select(
            'work_order_details.id',
            DB::raw('COALESCE(price_list_services.service_type_id, quotation_details.service_type_id) AS service_type_id'),
            DB::raw('COALESCE(price_list_services.name, quotation_detail_services.name) AS service_name'),
            DB::raw('COALESCE(price_list_services.is_overtime, quotation_detail_services.is_overtime) AS is_overtime'),
            DB::raw('COALESCE(price_lists.handling_type, quotation_details.handling_type) AS handling_type'),
            DB::raw('COALESCE(price_lists.free_storage_day, quotation_details.free_storage_day) AS free_storage_day'),
            DB::raw('COALESCE(price_lists.over_storage_price, quotation_details.over_storage_price) AS over_storage_price'),
            DB::raw('COALESCE(price_lists.price_volume, quotation_details.price_inquery_handling_volume) AS price_volume'),
            DB::raw('COALESCE(price_lists.price_tonase, quotation_details.price_inquery_handling_tonase) AS price_tonase'),
            DB::raw('COALESCE(price_lists.price_item, quotation_details.price_inquery_item) AS price_item'),
            DB::raw('COALESCE(price_lists.price_full, quotation_details.price_inquery_full) AS price_borongan'),
            DB::raw('COALESCE(price_lists.min_volume, quotation_details.price_inquery_min_volume) AS min_volume'),
            DB::raw('COALESCE(price_lists.min_tonase, quotation_details.price_inquery_min_tonase) AS min_tonase'),
            DB::raw('COALESCE(price_lists.min_item, quotation_details.price_inquery_min_item) AS min_item')
        );

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menambah qty limit
      Developer : Didin
      Status : Create
    */
    public static function increaseQty($qty, $id) {
        self::validate($id);

        DB::table(self::$table)
        ->whereId($id)
        ->increment('qty_leftover', $qty);
    }
}

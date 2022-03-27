<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use App\Abstracts\Setting\Checker;
use App\Model\QuotationDetail AS QD;
use App\Abstracts\Setting\Imposition;
use App\Model\Quotation AS Quo;
use App\Abstracts\Marketing\Lead;
use App\Utils\TransactionCode;

class Quotation
{
    protected static $table = 'quotations';

    /*
      Date : 29-08-2020
      Description : Menyimpan harga khusus untuk barang berdasarkan quotation
      Developer : Didin
      Status : Create
    */
    public static function storeQuotationItem($quotation_id, $item_id, $price = 0) {
        $exist = DB::table('quotation_items')
        ->whereQuotationId($quotation_id)
        ->whereItemId($item_id)
        ->first();

        $params = [];
        $params['price'] = $price ?? 0;
        if($exist) {
            DB::table('quotation_items')
            ->whereId($exist->id)
            ->update($params);
        } else {
            $params['quotation_id'] = $quotation_id;
            $params['item_id'] = $item_id;
            DB::table('quotation_items')
            ->insert($params);
        }       
    }
    
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
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Quotation not found');
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
        $dt = Quo::with('user_create','piece:id,name','customer:id,name,address','sales:id,name','customer_stage')->where(self::$table . '.id', $id)->first();

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Manangkap parameter untuk input data 
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params = [];
        $params['is_sales_contract'] = $args['is_sales_contract'] ?? 0;
        $params['company_id'] = $args['company_id'] ?? null;
        $params['customer_id'] = $args['customer_id'] ?? null;
        $params['sales_id'] = $args['sales_id'] ?? null;
        $params['customer_stage_id'] = $args['customer_stage_id'] ?? null;
        $params['created_by'] = $args['created_by'] ?? null;
        $params['no_inquery'] = $args['no_inquery'] ?? null;
        $params['bill_type'] = $args['bill_type'] ?? null;
        $params['send_type'] = $args['send_type'] ?? null;
        $params['price_full_inquery'] = $args['price_full_inquery'] ?? 0;
        $params['date_inquery'] = $args['date_inquery'] ?? null;
        if($params['date_inquery']) {
            $params['date_inquery'] = dateDB($params['date_inquery']);
        }
        $params['description_inquery'] = $args['description_inquery'] ?? null;
        $params['type_entry'] = $args['type_entry'] ?? 1;
        $params['name'] = $args['name'] ?? null;
        $params['imposition'] = $args['imposition'] ?? null;
        $params['piece_id'] = $args['piece_id'] ?? null;

        return $params;
    }

    /*
      Date : 08-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
        $template_contract_id = $params['template_contract_id'] ?? null;
        $is_generate = $params['is_generate'] ?? null;
        $inquery_id = $params['inquery_id'] ?? null;
        $insert = self::fetch($params);
        $insert['is_active'] = 1;
        $code = new TransactionCode(auth()->user()->company_id, "quotation");
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['code'] = $trx_code;
        $id = DB::table(self::$table)->insertGetId($insert);

        self::cloneContract($template_contract_id, $id);

        if($is_generate) {
            Inquery::release($insert['date_inquery'], $id, $inquery_id);
        }

        return $id;
    }


    /*
      Date : 12-02-2021
      Description : Set lead id
      Developer : Didin
      Status : Create
    */
    public static function setLead($lead_id, $id) {
        self::validate($id);
        Lead::validate($lead_id);
        DB::table(self::$table)->whereId($id)->update([
            "lead_id" => $lead_id
        ]);
    }

    /*
      Date : 12-02-2021
      Description : Menyalin kontrak
      Developer : Didin
      Status : Create
    */
      public static function cloneContract($origin_id, $destination_id) {
        if($origin_id && $destination_id) {
            $q = Quo::find($origin_id);
            $details = $q->quotation_detail;
            $destinationQuotation = Quo::find($destination_id);
            foreach($details as $detail) {
              $newDetail = $destinationQuotation->quotation_detail()->create([
                  'service_id' => $detail->service_id,
                  'price_list_id' => $detail->price_list_id,
                  'route_id' => $detail->route_id,
                  'combined_price_id' => $detail->combined_price_id,
                  'price_type' => $detail->price_type,
                  'piece_id' => $detail->piece_id,
                  'commodity_id' => $detail->commodity_id,
                  'moda_id' => $detail->moda_id,
                  'piece_name' => $detail->piece_name,
                  'vehicle_type_id' => $detail->vehicle_type_id,
                  'description' => $detail->description,
                  'imposition' => $detail->imposition,
                  'total' => $detail->total,
                  'price_inquery_tonase' => $detail->price_inquery_tonase,
                  'price_contract_tonase' => $detail->price_contract_tonase,
                  'price_inquery_volume' => $detail->price_inquery_volume,
                  'price_contract_volume' => $detail->price_contract_volume,
                  'price_inquery_item' => $detail->price_inquery_item,
                  'price_contract_item' => $detail->price_contract_item,
                  'price_inquery_full' => $detail->price_inquery_full,
                  'price_contract_full' => $detail->price_contract_full,
                  'cost' => $detail->cost,
                  'is_generate' => $detail->is_generate,
                  'price_inquery_handling_tonase' => $detail->price_inquery_handling_tonase,
                  'price_contract_handling_tonase' => $detail->price_contract_handling_tonase,
                  'price_inquery_handling_volume' => $detail->price_inquery_handling_volume,
                  'price_contract_handling_volume' => $detail->price_contract_handling_volume,
                  'rack_id' => $detail->rack_id,
                  'container_type_id' => $detail->container_type_id,
                  'service_type_id' => $detail->service_type_id,
                  'price_inquery_min_tonase' => $detail->price_inquery_min_tonase,
                  'price_contract_min_tonase' => $detail->price_contract_min_tonase,
                  'price_inquery_min_volume' => $detail->price_inquery_min_volume,
                  'price_contract_min_volume' => $detail->price_contract_min_volume,
                  'price_inquery_min_item' => $detail->price_inquery_min_item,
                  'price_contract_min_item' => $detail->price_contract_min_item,
                  'route_cost_id' => $detail->route_cost_id,
                  'price_list_price_full' => $detail->price_list_price_full,
                  'price_list_price_tonase' => $detail->price_list_price_tonase,
                  'price_list_price_volume' => $detail->price_list_price_volume,
                  'price_list_price_item' => $detail->price_list_price_item,
                  'warehouse_id' => $detail->warehouse_id,
                  'slug' => $detail->slug,
                  'free_storage_day' => $detail->free_storage_day ?? 0,
                  'over_storage_price' => $detail->over_storage_price
              ]);
              $costs = $detail->quotation_cost;
              foreach ($costs as $cost) {
                  $newCost = $newDetail->quotation_cost()->create([
                      'cost_type_id' => $cost->cost_type_id,
                      'vendor_id' => $cost->vendor_id,
                      'total' => $cost->total,
                      'cost' => $cost->cost,
                      'description' => $cost->description,
                      'is_internal' => $cost->is_internal,
                      'quotation_detail_id' => $cost->quotation_detail_id,
                      'route_cost_id' => $cost->route_cost_id,
                      'total_cost' => $cost->total_cost
                  ]);
              }

              $prices = $detail->quotation_price_detail;
              foreach ($prices as $price) {
                  $newPrice = $newDetail()->quotation_price_detail()->create([
                      'service_id' => $price->service_id,
                      'price' => $price->price
                  ]);
              }
            }
        }
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

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
use Response;

class PriceList extends Model
{
    protected $guarded = ['id'];

    public static function boot() {
        parent::boot();

        static::updating(function(PriceList $priceList){
            if($priceList->combined_price_id == null) {
                $detail = DB::table('price_list_details')->whereHeaderId( $priceList->id );      
                if( $detail->count('id') > 0 ) {
                    $detail->delete();
                }
            }
        });
        static::creating(function(PriceList $priceList){
            $priceList->is_warehouse = 0;
        });
    }

    public function setCombinedPriceIdAttribute($value)
    {
        if(isset( $value )) {
            $this->attributes['combined_price_id'] = $value;
            $this->attributes['price_type'] = 'paket';
            $service = DB::table('services')->first();
            $this->attributes['service_id'] = $service->id;
        } else {
            $this->attributes['combined_price_id'] = null;
            $this->attributes['price_type'] = 'service';  
        }
    }

    public function setServiceIdAttribute($value)
    {
            if(!isset($value)) {
                $service = DB::table('services')->first();
                $value = $service->id;
            }
            $this->attributes['service_id'] = $value;
            $service = DB::table('services')->whereId($value)->first();
            $this->attributes['service_type_id'] = $service->service_type_id;
        
    }

    public function getPriceNameAttribute($value)
    {
        if(isset( $this->attributes['combined_price_id'] )) {
            $combinedPrice = DB::table('combined_prices')->whereId($this->attributes['combined_price_id'])->first();
            return $combinedPrice->name;
        }
        else {
            $service = DB::table('services')->whereId($this->attributes['service_id'])->first();
            return $service->name;

        }
    }

    public function getPriceTypeNameAttribute($value)
    {
        if(isset( $this->attributes['combined_price_id'] )) {
            return 'Paket';
        }
        else {
            $service_type = DB::table('service_types')->whereId($this->attributes['service_type_id'])->first();
            return $service_type->name;

        }
    }

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }

    public function commodity()
    {
        return $this->belongsTo('App\Model\Commodity','commodity_id','id');
    }

    public function service()
    {
        return $this->belongsTo('App\Model\Service','service_id','id');
    }

    public function piece()
    {
        return $this->belongsTo('App\Model\Piece','piece_id','id');
    }

    public function route()
    {
        return $this->belongsTo('App\Model\Route','route_id','id');
    }

    public function moda()
    {
        return $this->belongsTo('App\Model\Moda','moda_id','id');
    }

    public function vehicle_type()
    {
        return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
    }

    public function rack()
    {
        return $this->belongsTo('App\Model\Rack','rack_id','id');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
    }

    public function container_type()
    {
        return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
    }

    public function service_type()
    {
        return $this->belongsTo('App\Model\ServiceType','service_type_id','id');
    }
    public function detail()
    {
        return $this->hasMany('App\Model\PriceListDetail','header_id','id');
    }

}

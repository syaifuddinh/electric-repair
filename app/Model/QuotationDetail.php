<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class QuotationDetail extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['imposition_name'];

  public static function boot() {
    parent::boot();

    static::updating(function(QuotationDetail $quotationDetail){
        if($quotationDetail->combined_price_id == null) {
            $detail = DB::table('quotation_price_details')->whereHeaderId( $quotationDetail->id );  
            if( $detail->count('id') > 0 ) {
                $detail->delete();
            }
        } else {
            $quotationDetail->vehicle_type_id = null;
            $quotationDetail->moda_id = null;
            $quotationDetail->container_type_id = null;
            $quotationDetail->commodity_id = null;
            $quotationDetail->route_id = null;    
            $quotationDetail->piece_id = null;    
            $quotationDetail->piece_name = null;    
        }
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

public function header()
{
  return $this->belongsTo('App\Model\Quotation','header_id','id');
}

public function quotation_cost()
{
    return $this->hasMany('App\Model\QuotationCost','quotation_detail_id','id');
}

public function quotation_price_detail()
{
    return $this->hasMany('App\Model\QuotationPriceDetail','header_id','id');
}

public function price_list()
{
  return $this->belongsTo('App\Model\PriceList','price_list_id','id');
}
public function commodity()
{
  return $this->belongsTo('App\Model\Commodity','commodity_id','id');
}
public function container_type()
{
  return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
}
public function rack()
{
  return $this->belongsTo('App\Model\Rack','rack_id','id');
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

public function cost_details()
{
  return $this->hasMany('App\Model\QuotationCost','quotation_detail_id','id');
}

public function getImpositionNameAttribute($value)
{
    $stt=[
      1 => 'Kubikasi',
      2 => 'Tonase',
      3 => 'Item',
      4 => 'Borongan'
  ];
  if (isset($this->attributes['imposition'])) {
      return $stt[$this->attributes['imposition']];
  } else {
      return "-";
  }
}

public function detail()
{
    return $this->hasMany('App\Model\QuotationPriceDetail','header_id','id');
}

}

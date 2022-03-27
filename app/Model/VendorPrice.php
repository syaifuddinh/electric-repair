<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VendorPrice extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
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

  public function container_type()
  {
      return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
  }

  public function service_type()
  {
      return $this->belongsTo('App\Model\ServiceType','service_type_id','id');
  }

}

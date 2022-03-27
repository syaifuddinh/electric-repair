<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function vehicle_variant()
  {
    return $this->belongsTo('App\Model\VehicleVariant','vehicle_variant_id','id');
  }

  public function supplier()
  {
      return $this->belongsTo('App\Model\Contact','supplier_id','id');
  }

  public function vehicle_owner()
  {
    return $this->belongsTo('App\Model\VehicleOwner','vehicle_owner_id','id');
  }

}

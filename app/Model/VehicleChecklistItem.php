<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleChecklistItem extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }

}

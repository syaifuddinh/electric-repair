<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleVariant extends Model
{
  protected $guarded = ['id'];

  public function vehicle_manufacturer()
  {
      return $this->belongsTo('App\Model\VehicleManufacturer','vehicle_manufacturer_id','id');
  }

  public function vehicle_type()
  {
      return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
  }

  public function vehicle_joint()
  {
      return $this->belongsTo('App\Model\VehicleJoint','vehicle_joint_id','id');
  }

}

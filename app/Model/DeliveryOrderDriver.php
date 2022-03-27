<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderDriver extends Model
{
  protected $guarded = ['id'];

  public function manifest()
  {
      return $this->belongsTo('App\Model\Manifest','manifest_id','id');
  }
  public function job_status()
  {
      return $this->belongsTo('App\Model\JobStatus','job_status_id','id');
  }
  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }
  public function driver()
  {
      return $this->belongsTo('App\Model\Contact','driver_id','id');
  }
  public function to()
  {
      return $this->belongsTo('App\Model\Contact','to_id','id');
  }

  public function rejected_by()
  {
    return $this->belongsTo('App\User','cancelled_by','id');
  }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function voyage_schedule()
  {
      return $this->belongsTo('App\Model\VoyageSchedule','voyage_schedule_id','id');
  }
  public function container_type()
  {
      return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
  }
  public function manifests()
  {
      return $this->hasMany('App\Model\Manifest','container_id','id');
  }
  public function commodity()
  {
      return $this->belongsTo('App\Model\Commodity','commodity_id','id');
  }
}

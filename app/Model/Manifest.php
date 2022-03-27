<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['status_name'];

  public function delivery()
  {
      return $this->hasOne('App\Model\DeliveryOrderDriver','manifest_id','id');
  }
  public function vehicle_type()
  {
      return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
  }
  public function user_create()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function container_type()
  {
      return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
  }
  public function trayek()
  {
      return $this->belongsTo('App\Model\Route','route_id','id');
  }
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }
  public function driver()
  {
      return $this->belongsTo('App\Model\Contact','driver_id','id');
  }
  public function container()
  {
      return $this->belongsTo('App\Model\Container','container_id','id');
  }
  public function route()
  {
      return $this->belongsTo('App\Model\Route','route_id','id');
  }
  public function details()
  {
      return $this->hasMany('App\Model\ManifestDetail','header_id','id');
  }
  public function getStatusNameAttribute()
  {
    try {
      $stt=[
        1 => 'Packing List',
        2 => 'Berangkat',
        3 => 'Sampai',
        4 => 'Selesai',
      ];
      return $stt[$this->attributes['status']];
    } catch (\Exception $e) {
      return null;
    }
  }
}

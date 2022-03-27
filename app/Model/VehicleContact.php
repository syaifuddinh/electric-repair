<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleContact extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['active_name','status_name'];

  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }
  public function contact()
  {
      return $this->belongsTo('App\Model\Contact','contact_id','id');
  }
  public function getActiveNameAttribute()
  {
    try {
      $stt=[
        1=>'AKTIF',
        2=>'TIDAK AKTIF'
      ];
      return $stt[$this->attributes['is_active']];
    } catch (\Exception $e) {
      return "";
    }

  }
  public function getStatusNameAttribute()
  {
    try {
      $stt=[
        1=>'Driver Utama',
        2=>'Driver Cadangan',
        3=>'Helper',
        4=>'Driver Eksternal'
      ];
      if (isset($this->attributes['driver_status'])) {
        return $stt[$this->attributes['driver_status']];
      } else {
        return "";
      }

    } catch (\Exception $e) {
      return "";

    }

  }
}

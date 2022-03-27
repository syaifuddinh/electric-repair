<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleInsurance extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['active_name','type_name'];

  public function insurance()
  {
      return $this->belongsTo('App\Model\Contact','insurance_id','id');
  }

  public function getActiveNameAttribute()
  {
    $stt=[
      1=>'Berlaku',
      0=>'Tidak Berlaku'
    ];
    return $stt[$this->attributes['is_active']];
  }

  public function getTypeNameAttribute()
  {
    $stt=[
      1=>'TLO',
      2=>'All Risk'
    ];
    return $stt[$this->attributes['type']];
  }

}

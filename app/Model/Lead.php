<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
  protected $guarded = ['id'];
  protected $appends = [
    'active_name',
  ];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function lead_source()
  {
      return $this->belongsTo('App\Model\LeadSource','lead_source_id','id');
  }
  public function lead_status()
  {
      return $this->belongsTo('App\Model\LeadStatus','lead_status_id','id');
  }
  public function industry()
  {
      return $this->belongsTo('App\Model\Industry','industry_id','id');
  }
  public function sales()
  {
      return $this->belongsTo('App\Model\Contact','sales_id','id');
  }
  public function city()
  {
      return $this->belongsTo('App\Model\City','city_id','id');
  }
  public function getActiveNameAttribute()
  {
    $stt=[
      1 => 'AKTIF',
      0 => 'TIDAK AKTIF',
    ];
    if (isset($this->attributes['is_active'])) {
      return $stt[$this->attributes['is_active']];
    } else {
      return null;
    }
  }
}

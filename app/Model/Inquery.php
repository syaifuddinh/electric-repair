<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Inquery extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['status_name'];

  public function sales_opportunity()
  {
      return $this->belongsTo('App\Model\Contact','sales_opportunity_id','id');
  }
  public function sales_inquery()
  {
      return $this->belongsTo('App\Model\Contact','sales_inquery_id','id');
  }
  public function customer_stage()
  {
      return $this->belongsTo('App\Model\CustomerStage','customer_stage_id','id');
  }
  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function getStatusNameAttribute()
  {
    try {
      $stt=[
        1 => 'Opportunity',
        2 => 'Inquery',
        3 => 'Quotation',
        4 => 'Contract',
      ];
      return $stt[$this->attributes['status']];
    } catch (\Exception $e) {
      return null;
    }
  }
}

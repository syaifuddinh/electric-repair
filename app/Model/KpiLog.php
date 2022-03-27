<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class KpiLog extends Model
{
  protected $guarded = ['id'];

  public function kpi_status()
  {
      return $this->belongsTo('App\Model\KpiStatus','kpi_status_id','id');
  }
  public function creates()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function job_order()
  {
      return $this->belongsTo('App\Model\JobOrder','job_order_id','id');
  }
}

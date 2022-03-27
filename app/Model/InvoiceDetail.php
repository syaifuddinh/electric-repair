<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
  protected $guarded = ['id'];

  public function invoice()
  {
      return $this->belongsTo('App\Model\Invoice','header_id','id');
  }
  public function job_order()
  {
      return $this->belongsTo('App\Model\JobOrder','job_order_id','id');
  }
  public function work_order()
  {
      return $this->belongsTo('App\Model\WorkOrder','work_order_id','id');
  }
  public function cost_type()
  {
      return $this->belongsTo('App\Model\CostType','cost_type_id','id');
  }
  public function manifest()
  {
      return $this->belongsTo('App\Model\Manifest','manifest_id','id');
  }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class JobOrderCost extends Model
{
  protected $guarded = ['id'];

  public function header()
  {
      return $this->belongsTo('App\Model\JobOrder','header_id','id');
  }
  public function cost_type()
  {
      return $this->belongsTo('App\Model\CostType','cost_type_id','id');
  }
  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }
  public function submission_cost()
  {
      return $this->hasOne('App\Model\SubmissionCost','relation_cost_id','id');
  }
  public function approve()
  {
    return $this->belongsTo('App\User','approve_by','id');
  }
  public function user_create()
  {
    return $this->belongsTo('App\User','create_by','id');
  }
}

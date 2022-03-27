<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RouteCostDetail extends Model
{
  protected $guarded = ['id'];

  public function cost_type()
  {
      return $this->belongsTo('App\Model\CostType','cost_type_id','id');
  }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TargetRate extends Model
{
  protected $guarded = ['id'];

  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }

}

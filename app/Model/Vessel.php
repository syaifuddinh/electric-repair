<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
  protected $guarded = ['id'];

  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }
}

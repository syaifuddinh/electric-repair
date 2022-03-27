<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CostType extends Model
{
  protected $guarded = ['id'];

  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }

  public function parent()
  {
      return $this->belongsTo('App\Model\CostType','parent_id','id');
  }

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

}

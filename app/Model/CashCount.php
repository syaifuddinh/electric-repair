<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashCount extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function approved_by()
  {
      return $this->belongsTo('App\User','approved_by_id','id');
  }
}

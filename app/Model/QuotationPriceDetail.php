<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class QuotationPriceDetail extends Model
{
  protected $guarded = ['id'];

  public function service()
  {
      return $this->belongsTo('App\Model\Service','service_id','id');
  }
}

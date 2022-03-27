<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentDetail extends Model
{
  protected $guarded = ['id'];

  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }
}

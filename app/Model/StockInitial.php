<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockInitial extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }

  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }
}

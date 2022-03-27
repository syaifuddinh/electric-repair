<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
  protected $guarded = ['id'];

  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }
  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }
}

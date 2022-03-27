<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Warehouserent extends Model
{
  protected $guarded = ['id'];

  public function warehouse()
	{
	    return $this->belongsTo('App\Model\Warehouse', 'warehouse_id', 'id');
	}
}

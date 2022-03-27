<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Handling extends Model
{
  protected $guarded = ['id'];

  public function handling_vehicle()
	{
	    return $this->belongsTo('App\Model\HandlingVehicle', 'id', 'handling_id');
	}

  public function warehouse()
	{
	    return $this->belongsTo('App\Model\Warehouse', 'warehouse_id', 'id');
	}
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PickingDetail extends Model
{
  protected $guarded = ['id'];

  public function item()
	{
	    return $this->belongsTo('App\Model\Item', 'item_id', 'id');
	}
  public function rack()
	{
	    return $this->belongsTo('App\Model\Rack', 'rack_id', 'id');
	}
}

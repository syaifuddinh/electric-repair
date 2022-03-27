<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UsingItemDetail extends Model
{
  protected $guarded = ['id'];

  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }
}

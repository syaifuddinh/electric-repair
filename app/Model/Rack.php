<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
  protected $guarded = ['id'];

  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }
  public function storage_type()
  {
      return $this->belongsTo('App\Model\StorageType','storage_type_id','id');
  }
}

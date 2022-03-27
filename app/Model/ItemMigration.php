<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ItemMigration extends Model
{
  protected $guarded = ['id'];

  public function warehouse_from()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_from_id','id');
  }
  public function warehouse_to()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_to_id','id');
  }
  public function rack_from()
  {
      return $this->belongsTo('App\Model\Rack','rack_from_id','id');
  }
  public function rack_to()
  {
      return $this->belongsTo('App\Model\Rack','rack_to_id','id');
  }


}

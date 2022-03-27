<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WarehouseReceiptRack extends Model
{
  protected $guarded = ['id'];

  public function header()
  {
      return $this->belongsTo('App\Model\WarehouseReceipt','header_id','id');
  }
  public function rack()
  {
      return $this->belongsTo('App\Model\Rack','rack_id','id');
  }

}

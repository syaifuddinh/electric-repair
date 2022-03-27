<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReceiptList extends Model
{
  protected $guarded = ['id'];

  public function receipt()
  {
      return $this->belongsTo('App\Model\Receipt','header_id','id');
  }
  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }
}

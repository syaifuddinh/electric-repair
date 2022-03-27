<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDetail extends Model
{
  protected $guarded = ['id'];

  public function vehicle()
  {
      return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
  }
  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }

  public function po_detail()
  {
    return $this->hasOne('App\Model\PurchaseOrderDetail', 'purchase_request_detail_id');
  }
}

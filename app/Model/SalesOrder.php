<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
  protected $guarded = ['id'];
  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }
  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }
  
  public function customer_order()
  {
      return $this->belongsTo('App\Model\CustomerOrder','customer_order_id','id');
  }

}

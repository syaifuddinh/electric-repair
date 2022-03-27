<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StokOpnameWarehouse extends Model
{
  protected $guarded = ['id'];

  public function warehouse()
	{
	    return $this->belongsTo('App\Model\Warehouse', 'warehouse_id', 'id');
	}
  public function warehouse_receipt()
	{
	    return $this->belongsTo('App\Model\WarehouseReceipt', 'warehouse_receipt_id', 'id');
	}
  public function customer()
	{
	    return $this->belongsTo('App\Model\Contact', 'customer_id', 'id');
	}
}

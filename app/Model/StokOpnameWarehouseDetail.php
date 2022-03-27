<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StokOpnameWarehouseDetail extends Model
{
  protected $guarded = ['id'];

  public function rack()
	{
	    return $this->belongsTo('App\Model\Rack', 'rack_id', 'id');
	}
  public function item()
	{
	    return $this->belongsTo('App\Model\Item', 'item_id', 'id');
	}
  public function warehouse_stock_detail()
	{
	    return $this->belongsTo('App\Model\WarehouseStockDetail', 'warehouse_stock_detail_id', 'id');
	}
  public function warehouse_receipt()
	{
	    return $this->belongsTo('App\Model\WarehouseReceipt', 'warehouse_receipt_id', 'id');
	}
  public function stok_opname_warehouse()
	{
	    return $this->belongsTo('App\Model\StokOpnameWarehouse', 'header_id', 'id');
	}
}

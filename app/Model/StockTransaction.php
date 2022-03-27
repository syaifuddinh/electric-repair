<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
  protected $guarded = ['id'];

  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }

  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }

  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }

  public function warehouse_receipt()
  {
      return $this->belongsTo('App\Model\WarehouseReceipt','no_surat_jalan', 'code');
  }

  public function warehouse_receipt_detail()
  {
      return $this->belongsTo('App\Model\WarehouseReceiptDetail','warehouse_receipt_detail_id', 'id');
  }


}

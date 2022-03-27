<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WorkOrderDetail extends Model
{
  protected $guarded = ['id'];

  public function header()
  {
      return $this->belongsTo('App\Model\WorkOrder','header_id','id');
  }
  public function quotation_detail()
  {
      return $this->belongsTo('App\Model\QuotationDetail','quotation_detail_id','id');
  }
  public function price_list()
  {
      return $this->belongsTo('App\Model\PriceList','price_list_id','id');
  }
  public function customer_price()
  {
      return $this->belongsTo('App\Model\CustomerPrice','customer_price_id','id');
  }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class QuotationCost extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['total_cost'];

  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }

  public function quotation_detail()
  {
      return $this->belongsTo('App\Model\QuotationDetail','quotation_detail_id','id');
  }

  public function cost_type()
  {
      return $this->belongsTo('App\Model\CostType','cost_type_id','id');
  }

  public function getTotalCostAttribute()
  {
    return $this->attributes['total']*$this->attributes['cost'];
  }
}

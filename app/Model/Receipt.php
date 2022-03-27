<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function purchase_order()
  {
      return $this->belongsTo('App\Model\PurchaseOrder','po_id','id');
  }
  public function lists()
  {
      return $this->hasMany('App\Model\ReceiptList','header_id','id');
  }

}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InvoiceVendor extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['is_lunas'];

  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }
  
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function find_payable()
  {
    return Payable::where('is_invoice',1)->where('relation_id',$this->id)->first();
  }

  public function getIsLunasAttribute()
  {
    $payable = $this->find_payable();
    if(is_null($payable))
        return false;

    return !($this->find_payable()->sisa_hutang > 0);
  }
}

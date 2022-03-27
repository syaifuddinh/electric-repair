<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ContactAddress extends Model
{
  protected $guarded = ['id'];

  public function contact()
  {
      return $this->belongsTo('App\Model\Contact','contact_id','id');
  }
  public function contact_address()
  {
      return $this->belongsTo('App\Model\Contact','contact_address_id','id');
  }
  public function contact_bill()
  {
      return $this->belongsTo('App\Model\Contact','contact_bill_id','id');
  }
  public function address_type()
  {
      return $this->belongsTo('App\Model\AddressType','address_type_id','id');
  }
}

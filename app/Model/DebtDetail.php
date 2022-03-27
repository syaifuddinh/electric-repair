<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DebtDetail extends Model
{
  protected $guarded = ['id'];

  public function type_transaction()
  {
      return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
  }

  public function payable()
  {
      return $this->belongsTo('App\Model\Payable','payable_id','id');
  }
  public function payableDetail()
  {
      return $this->belongsTo('App\Model\PayableDetail','payable_detail_id','id');
  }
}

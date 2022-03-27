<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
  protected $guarded = ['id'];

  public function type_transaction()
  {
      return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
  }
}

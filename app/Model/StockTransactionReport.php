<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockTransactionReport extends Model
{
  protected $guarded = ['id'];
  protected $table = 'stock_transactions_report';

  public function stock_transaction()
  {
      return $this->belongsTo('App\Model\StockTransaction','header_id','id');
  }
}

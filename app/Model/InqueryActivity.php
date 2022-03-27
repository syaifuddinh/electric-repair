<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InqueryActivity extends Model
{
  protected $guarded = ['id'];
  public function sales()
  {
      return $this->belongsTo('App\Model\Contact','sales_id','id');
  }
  public function customer_stage()
  {
      return $this->belongsTo('App\Model\CustomerStage','customer_stage_id','id');
  }

}

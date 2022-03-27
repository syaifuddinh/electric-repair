<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ManifestDetail extends Model
{
  protected $guarded = ['id'];

  public function job_order_detail()
  {
      return $this->belongsTo('App\Model\JobOrderDetail','job_order_detail_id','id');
  }
  public function manifest()
  {
      return $this->belongsTo('App\Model\Manifest','header_id','id');
  }
}

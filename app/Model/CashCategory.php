<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashCategory extends Model
{
  protected $guarded = ['id'];

  public function category()
  {
      return $this->belongsTo('App\Model\CashCategory','parent_id','id');
  }

  public function details()
  {
      return $this->hasMany('App\Model\CashCategoryDetail','header_id','id');
  }
}

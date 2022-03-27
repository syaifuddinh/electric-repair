<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
  protected $guarded = ['id'];

  public function parent()
  {
      return $this->belongsTo('App\Model\Category','parent_id','id');
  }
}

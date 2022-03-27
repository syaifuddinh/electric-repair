<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GroupType extends Model
{
  protected $guarded = ['id'];

  public function users()
  {
    return $this->hasMany('App\User','group_id');
  }
}

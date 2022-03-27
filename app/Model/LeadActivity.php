<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
  protected $guarded = ['id'];
  public function creates()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
}

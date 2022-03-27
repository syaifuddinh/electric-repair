<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubmissionCost extends Model
{
  protected $guarded = ['id'];
  public function created_by_user()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function approve_by_user()
  {
      return $this->belongsTo('App\User','approve_by','id');
  }
}

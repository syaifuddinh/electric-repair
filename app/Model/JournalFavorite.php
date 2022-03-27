<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class JournalFavorite extends Model
{
  protected $guarded = ['id'];

  public function details()
  {
      return $this->hasMany('App\Model\JournalFavoriteDetail','header_id','id');
  }

}

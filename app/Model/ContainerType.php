<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ContainerType extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['full_name'];
  protected $hidden = ['created_at', 'updated_at'];

  public function getFullNameAttribute()
  {
    return $this->attributes['code'].' - '.$this->attributes['name'].' ('.$this->attributes['size']. ' ' . $this->attributes['unit'] . ')';
  }
}

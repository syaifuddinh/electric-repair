<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
  protected $guarded = ['id'];
  protected $casts = [
    'parameter' => 'array'
  ];
}

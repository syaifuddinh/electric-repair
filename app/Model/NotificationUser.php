<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NotificationUser extends Model
{
  protected $guarded = ['id'];

  public function notification()
  {
      return $this->belongsTo('App\Model\Notification', 'notification_id', 'id');
  }

}

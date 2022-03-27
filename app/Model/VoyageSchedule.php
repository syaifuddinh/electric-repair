<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VoyageSchedule extends Model
{
  protected $guarded = ['id'];

  public function vessel()
  {
      return $this->belongsTo('App\Model\Vessel','vessel_id','id');
  }
  public function countries()
  {
      return $this->belongsTo('App\Model\Countries','countries_id','id');
  }
  public function pol()
  {
      return $this->belongsTo('App\Model\Port','pol_id','id');
  }
  public function pod()
  {
      return $this->belongsTo('App\Model\Port','pod_id','id');
  }
  public function created_by()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleChecklistDetailItem extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['exist_name_html','function_name_html','condition_html'];

  public function vehicle_checklist()
  {
      return $this->belongsTo('App\Model\VehicleChecklist','vehicle_checklist_id','id');
  }
  public function getExistNameHtmlAttribute()
  {
    $stt=[
      1=>'<span class="badge badge-success"><i class="fa fa-check"></i> YA</span>',
      0=>'<span class="badge badge-danger"><i class="fa fa-times"></i> TIDAK</span>'
    ];
    return $stt[$this->attributes['is_exist']];
  }
  public function getConditionHtmlAttribute()
  {
    $stt=[
      1=>'<span class="badge badge-success"><i class="fa fa-check"></i> BAIK</span>',
      2=>'<span class="badge badge-danger"><i class="fa fa-times"></i> TIDAK BAIK</span>'
    ];
    return $stt[$this->attributes['condition']] ?? '';
  }
  public function getFunctionNameHtmlAttribute()
  {
    $stt=[
      1=>'<span class="badge badge-success"><i class="fa fa-check"></i> YA</span>',
      0=>'<span class="badge badge-danger"><i class="fa fa-times"></i> TIDAK</span>'
    ];
    return $stt[$this->attributes['is_function']];
  }

}

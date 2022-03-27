<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Closing extends Model
{
  protected $table = 'closing';
  protected $fillable = [
    'company_id',
    'status',
    'start_periode',
    'end_periode',
    'closing_date',
    'description',
    'is_lock',
    'is_depresiasi',
    'journal_id'
  ];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

}

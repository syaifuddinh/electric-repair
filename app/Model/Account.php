<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    //
    protected $guarded = ['id'];
    protected $appends=['jenis_name','group_report_name','account_name'];

    public function parent()
    {
        return $this->belongsTo('App\Model\Account','parent_id','id');
    }

    public function type()
    {
        return $this->belongsTo('App\Model\AccountType','type_id','id');
    }

    public function getJenisNameAttribute()
    {
      try {
        $stt=[
          1=>'DEBET',
          2=>'KREDIT',
        ];
        return $stt[$this->attributes['jenis']];
      } catch (\Exception $e) {
        return null;
      }
    }
    public function getGroupReportNameAttribute()
    {
      try {
        $stt=[
          1=>'NERACA',
          2=>'LABA RUGI',
        ];
        return $stt[$this->attributes['group_report']];
      } catch (\Exception $e) {
        return null;
      }
    }
    public function getAccountNameAttribute()
    {
      return $this->attributes['code'].' - '.$this->attributes['name'];
    }
}

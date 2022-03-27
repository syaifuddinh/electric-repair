<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UsingItem extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['status_name_html'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function vehicle()
    {
        return $this->belongsTo('App\Model\Vehicle','vehicle_id','id');
    }
    public function getStatusNameHtmlAttribute()
    {
      $stt=[
        1 => '<span class="badge badge-primary">Pengajuan</span>',
        2 => '<span class="badge badge-warning">Disetujui</span>',
        3 => '<span class="badge badge-info">Proses Penggunaan</span>',
        4 => '<span class="badge badge-success">Selesai</span>',
      ];
      return $stt[$this->attributes['status']];
    }

}

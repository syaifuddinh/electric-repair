<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Retur extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['status_name_html'];

    public function receipt_list()
    {
        return $this->belongsTo('App\Model\ReceiptList','receipt_list_id','id');
    }
    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function warehouse()
    {
        return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
    }
    public function supplier()
    {
        return $this->belongsTo('App\Model\Contact','supplier_id','id');
    }
    public function getStatusNameHtmlAttribute()
    {
      $stt=[
        1 => '<span class="badge badge-warning">Barang Belum Kembali</span>',
        2 => '<span class="badge badge-info">Barang Diterima Sebagian</span>',
        3 => '<span class="badge badge-primary">Barang Kembali</span>',
        4 => '<span class="badge badge-success">Retur Cash</span>',
      ];
      if (isset($this->attributes['status'])) {
        return $stt[$this->attributes['status']];
      } else {
        return "";
      }
    }
}

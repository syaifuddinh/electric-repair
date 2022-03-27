<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
  protected $guarded = ['id'];
  protected $hidden = ['created_at', 'updated_at'];
  // protected $appends = [
  //   'active_name',
  //   'status_contact_name',
  //   'approve_name',
  //   'driver_status_name',
  // ];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function bank()
  {
      return $this->belongsTo('App\Model\Bank','rek_bank_id','id');
  }

  public function tax()
  {
      return $this->belongsTo('App\Model\Tax','tax_id','id');
  }

  public function hutang()
  {
      return $this->belongsTo('App\Model\Account','akun_hutang','id');
  }

  public function piutang()
  {
      return $this->belongsTo('App\Model\Account','akun_piutang','id');
  }

  public function um_supplier()
  {
      return $this->belongsTo('App\Model\Account','akun_um_supplier','id');
  }

  public function sales()
  {
      return $this->belongsTo('App\Model\Contact','sales_id','id');
  }

  public function customer_service()
  {
      return $this->belongsTo('App\Model\Contact','customer_service_id','id');
  }

  public function um_customer()
  {
      return $this->belongsTo('App\Model\Account','akun_um_customer','id');
  }

  public function vendor_type()
  {
      return $this->belongsTo('App\Model\VendorType','vendor_type_id','id');
  }

  public function city()
  {
      return $this->belongsTo('App\Model\City','city_id','id');
  }

  public function getActiveNameAttribute()
  {
    $stt=[
      1 => 'AKTIF',
      0 => 'TIDAK AKTIF',
    ];
    if (isset($this->attributes['is_active'])) {
      return $stt[$this->attributes['is_active']];
    } else {
      return null;
    }
  }
  public function getDriverStatusNameAttribute()
  {
    $stt=[
      1 => 'Driver Utama',
      2 => 'Driver Cadangan',
      3 => 'Helper',
      4 => 'Driver Vendor',
    ];
    if (isset($this->attributes['driver_status'])) {
      return $stt[$this->attributes['driver_status']];
    } else {
      return null;
    }
  }
  public function getApproveNameAttribute()
  {
    $stt=[
      1 => '<span class="badge badge-danger">PENDING</span>',
      2 => '<span class="badge badge-primary">VERIFIED</span>',
    ];
    // return $stt[$this->is_active];
    // return "{$this->is_active}";
    if (isset($this->attributes['is_active'])) {
      return $stt[$this->attributes['vendor_status_approve']];
    } else {
      return null;
    }
  }

  public function getStatusContactNameAttribute()
  {
    $stt="";
    try {
      if ($this->attributes['is_pegawai']) {
        $stt.="Pegawai, ";
      }
      if ($this->attributes['is_investor']) {
        $stt.="Investor, ";
      }
      if ($this->attributes['is_pelanggan']) {
        $stt.="Customer, ";
      }
      if ($this->attributes['is_asuransi']) {
        $stt.="Asuransi, ";
      }
      if ($this->attributes['is_supplier']) {
        $stt.="Supplier, ";
      }
      if ($this->attributes['is_depo_bongkar']) {
        $stt.="Depo Bongkar, ";
      }
      if ($this->attributes['is_helper']) {
        $stt.="Helper, ";
      }
      if ($this->attributes['is_driver']) {
        $stt.="Driver, ";
      }
      if ($this->attributes['is_vendor']) {
        $stt.="Vendor, ";
      }
      if ($this->attributes['is_sales']) {
        $stt.="Sales, ";
      }
      if ($this->attributes['is_kurir']) {
        $stt.="Kurir, ";
      }
      if ($this->attributes['is_pengirim']) {
        $stt.="Pengirim, ";
      }
      if ($this->attributes['is_penerima']) {
        $stt.="Penerima, ";
      }
      return $stt;
    } catch (\Exception $e) {
      return null;
    }

  }
}

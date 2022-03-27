<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
  protected $guarded = ['id'];

  protected $appends = ['kode_invoice'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function debtDetails()
  {
      return $this->hasMany('App\Model\DebtDetail','header_id','id');
  }

  public function getKodeInvoiceAttribute()
  {
    $kode = '';
    foreach($this->debtDetails as $debtDetail) {
        $detail = $debtDetail->payable;
        
        if(is_null($detail))
          continue;
        
        $_kode = $detail->code;
        if(!empty($_kode)) {
            if(!empty($kode))
                $_kode = ', ' . $_kode;
            
            $kode .= $_kode;
        }
    }
    return $kode;
  }
}

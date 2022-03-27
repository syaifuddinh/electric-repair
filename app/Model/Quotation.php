<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\CustomerPrice;
use DB;

class Quotation extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['bill_type_name','send_type_name','active_name','status_name','imposition_name'];

  public static function boot() {
        parent::boot();

        static::updating(function(Quotation $q){
            if($q->is_contract == 1) {
                $qd = DB::table('quotation_details')->whereHeaderId($q->id)->get();
                foreach($qd as $value) {
                    $detail = DB::table('quotation_price_details')->whereHeaderId($value->id );
                        if($detail->count('id') > 0) {
                            $detail_item =$detail->get();
                            DB::table('customer_price_details')->whereHeaderId($c->id)->delete();
                            foreach($detail_item as $unit) {
                              DB::table('customer_price_details')->insert([
                                'header_id' => $c->id,
                                'service_id' => $unit->service_id,
                                'price' => $unit->price
                              ]);
                            }
                        }
                }
            }
        });
    }

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function child()
  {
      return $this->hasOne('App\Model\Quotation','parent_id','id');
  }

  public function quotation_detail()
  {
      return $this->hasMany('App\Model\QuotationDetail','header_id','id');
  }

  public function quotation_item()
  {
      return $this->hasMany('App\Model\QuotationItem','quotation_id','id');
  }

  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }

  public function sales()
  {
      return $this->belongsTo('App\Model\Contact','sales_id','id');
  }

  public function customer_stage()
  {
      return $this->belongsTo('App\Model\CustomerStage','customer_stage_id','id');
  }

  public function user_create()
  {
      return $this->belongsTo('App\User','created_by','id');
  }
  public function inquery()
  {
      return $this->hasOne('App\Model\Inquery','quotation_id','id');
  }
  public function lead()
  {
      return $this->hasOne('App\Model\Lead','quotation_id','id');
  }
  public function piece()
  {
      return $this->belongsTo('App\Model\Piece','piece_id','id');
  }

  public function getSendTypeNameAttribute($value)
  {
    $stt = [
      0 => '',
      1 => "Sekali",
      2 => "Per Hari",
      3 => "Per Minggu",
      4 => "Per Bulan",
      5 => "Tidak Tentu",
    ];
    return $stt[$this->attributes['send_type']];
  }

  public function getBillTypeNameAttribute($value)
  {
    $stt=[
      1 => "Per Pengiriman",
      2 => "Borongan",
    ];
    return $stt[$this->attributes['bill_type']];
  }

  public function getActiveNameAttribute($value)
  {
    $stt=[
      0 => 'Tidak Aktif',
      1 => 'Aktif',
    ];
    return $stt[$this->attributes['is_active']];
  }
  public function getStatusNameAttribute($value)
  {
    if (empty($this->attributes['approve_by'])) {
      return "Belum Disetujui";
    } elseif (isset($this->attributes['approve_by']) && $this->attributes['is_contract']==0) {
      return "Sudah Persetujuan";
    } else {
      return "Sudah Kontrak";
    }
  }
  public function getImpositionNameAttribute($value)
  {
    try {
      $stt=[
        1 => "Kubikasi",
        2 => "Tonase",
        3 => "Item",
        4 => "Borongan",
      ];
      return $stt[$this->attributes['imposition']];
    } catch (\Exception $e) {
      return '';
    }
  }

}

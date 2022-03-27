<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class CombinedPrice extends Model
{
    //
    protected $fillable = ['code', 'name', 'company_id', 'total_item', 'created_by'];
    protected $appends = [ 'status', 'full_name'];

    public static function boot() {
        parent::boot();

        static::creating(function(CombinedPrice $c){            
            $c->created_by = auth()->id();
            $service_type = DB::table("service_types")->whereName('Paket layanan')->first();
            $id = DB::table('services')->insertGetId([
                'name' => $c->name,
                'service_type_id' => $service_type->id
            ]);

            $c->service_id = $id;
        });

    }

    public function getStatusAttribute()
    {
        $status = ['Tidak Aktif', 'Aktif'];
        return $status[$this->is_active] ?? null;
    }

    public function getFullNameAttribute()
    {
        $resp = CombinedPrice::find($this->id);
        return $resp->code . ' - ' . $resp->name;
    }

    public function actived()
    {
      return $this->whereIsActive(1);
    }

    public function company()
    {
      return $this->hasOne('App\Model\Company','id','company_id');
    }
    public function service()
    {
      return $this->belongsTo('App\Model\Service');
    }
    public function detail()
    {
      return $this->hasMany('App\Model\CombinedPriceDetail','header_id','id');
    }

}

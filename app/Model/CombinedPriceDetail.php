<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\CombinedPrice;
use App\Model\Service;

class CombinedPriceDetail extends Model
{
    protected $fillable = ['service_id', 'header_id'];

    public static function boot() {
        parent::boot();

        static::creating(function(CombinedPriceDetail $c){            
            $combinedPrice = CombinedPrice::find($c->header_id);
            $combinedPrice->increment('total_item');
            $combinedPrice->save();
            $service = Service::find($c->service_id);
            if($service->is_wh_rent == 1) {
                $relatedService = Service::find($combinedPrice->service_id);
                $relatedService->is_wh_rent = 1;
                $relatedService->save();
            }
        });

        static::deleting(function(CombinedPriceDetail $c){            
            CombinedPrice::find($c->header_id)->decrement('total_item');
        });
    }

    public function service()
    {
        return $this->belongsTo('App\Model\Service','service_id','id');
    }

}

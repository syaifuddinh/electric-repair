<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;
use App\Model\JobOrder;

class JobOrderDetail extends Model
{
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::deleting(function(JobOrderDetail $jobOrderDetail){
            $jo = JobOrder::find($jobOrderDetail->header_id);
            $jo->decrement('total_item', 1);
        });

        static::creating(function(JobOrderDetail $jobOrderDetail){
            if($jobOrderDetail->item_id != null) {
                $item = DB::table('items')
                ->whereId($jobOrderDetail->item_id)
                ->select('name')
                ->first();

                if($item)
                    $jobOrderDetail->item_name = $item->name;
            }

            // $jobOrderDetail->volume = $jobOrderDetail->long * $jobOrderDetail->wide * $jobOrderDetail->high * $jobOrderDetail->qty / 1000000;  
        });

        static::updating(function(JobOrderDetail $jobOrderDetail){
            if($jobOrderDetail->item_id != null) {
                $item = DB::table('items')
                ->whereId($jobOrderDetail->item_id)
                ->select('name')
                ->first();

                if($item)
                    $jobOrderDetail->item_name = $item->name;
            }
            
            // $jobOrderDetail->volume = $jobOrderDetail->long * $jobOrderDetail->wide * $jobOrderDetail->high * $jobOrderDetail->qty / 1000000;  
        });
    }

    public function job_order()
    {
        return $this->belongsTo('App\Model\JobOrder','header_id','id');
    }

    public function piece()
    {
        return $this->belongsTo('App\Model\Piece','piece_id','id');
    }
    
    public function warehouse_receipt_detail()
    {
        return $this->belongsTo('App\Model\WarehouseReceiptDetail','warehouse_receipt_detail_id','id');
    }

    public function manifest()
    {
        return $this->belongsTo('App\Model\Manifest','manifest_id','id');
    }
    
    public function manifest_detail()
    {
        return $this->hasOne('App\Model\ManifestDetail');
    }
}

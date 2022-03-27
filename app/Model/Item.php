<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Item extends Model
{
  protected $guarded = ['id'];

    public static function boot() {
        parent::boot();

        static::creating(function(Item $item){
        if(!$item->code) {
            $latest_item = Item::selectRaw('IFNULL(COUNT(id), 0) + 1 AS new_id')
            ->first();
            $id = str_pad($latest_item->new_id, 4, '0', STR_PAD_LEFT);
            $item->code = 'BRG' . $id;
        }
        });
      static::updating(function(Item $item){
          $volume = $item->long * $item->wide * $item->height ;
          DB::table('job_order_details')
          ->whereItemId($item->id)
          ->update([
                'long' => $item->long ,
                'wide' => $item->wide ,
                'high' => $item->height ,
                'weight' => $item->tonase ,
                'volume' => DB::raw("qty * $volume / 1000000"),
          ]);
      });
  }

  public function category()
  {
      return $this->belongsTo('App\Model\Category','category_id','id');
  }
  public function piece()
  {
      return $this->belongsTo('App\Model\Piece','piece_id','id');
  }
  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }
  public function sender()
  {
      return $this->belongsTo('App\Model\Contact','sender_id','id');
  }
  public function receiver()
  {
      return $this->belongsTo('App\Model\Contact','receiver_id','id');
  }
}

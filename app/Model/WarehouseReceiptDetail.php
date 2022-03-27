<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Item;
use DB;

class WarehouseReceiptDetail extends Model
{
  protected $guarded = ['id'];

  public static function boot() {
      parent::boot();

      static::creating(function(WarehouseReceiptDetail $warehouseReceiptDetail) {
          if($warehouseReceiptDetail->item_id == null) {
            $item_order = Item::whereRaw('0 = 0')->count('id') + 1;
            $existing_item = WarehouseReceiptDetail::where('item_name', $warehouseReceiptDetail->item_name)->whereRaw('item_id IS NOT NULL')->first();
            if($existing_item == null) {
              $piece = DB::table('pieces')->whereName('Item')->first();
              $i = Item::create([
                'code' => DB::raw("(SELECT CONCAT('BRG', LPAD($item_order, 3, 0)))"),
                'name' => $warehouseReceiptDetail->item_name,
                'piece_id' => $piece->id,
                'long' => $warehouseReceiptDetail->long ,
                'wide' => $warehouseReceiptDetail->wide ,
                'height' => $warehouseReceiptDetail->high ,
                'volume' => $warehouseReceiptDetail->long * $warehouseReceiptDetail->wide * $warehouseReceiptDetail->high ,
                'tonase' => $warehouseReceiptDetail->weight
              ]);
              $new_item_id = $i->id;
            }
            else {
              $new_item_id = $existing_item->item_id;
            }

            $warehouseReceiptDetail->item_id = $new_item_id;
          } else {
              $item = DB::table('items')->whereId($warehouseReceiptDetail->item_id)->first();
              $warehouseReceiptDetail->item_name = $item->name;
          }
      });

      static::updating(function(WarehouseReceiptDetail $warehouseReceiptDetail) {
          if($warehouseReceiptDetail->item_id == null) {
            $item_order = Item::whereRaw('0 = 0')->count('id') + 1;
            $existing_item = WarehouseReceiptDetail::where('item_name', $warehouseReceiptDetail->item_name)->whereRaw('item_id IS NOT NULL')->first();
            if($existing_item == null) {
              $piece = DB::table('pieces')->whereName('Item')->first();
              $i = Item::create([
                'code' => DB::raw("(SELECT CONCAT('BRG', LPAD($item_order, 3, 0)))"),
                'name' => $warehouseReceiptDetail->item_name,
                'piece_id' => $piece->id,
                'long' => $warehouseReceiptDetail->long ,
                'wide' => $warehouseReceiptDetail->wide ,
                'height' => $warehouseReceiptDetail->high ,
                'volume' => $warehouseReceiptDetail->long * $warehouseReceiptDetail->wide * $warehouseReceiptDetail->high ,
                'tonase' => $warehouseReceiptDetail->weight
              ]);
              $new_item_id = $i->id;
            }
            else {
              $new_item_id = $existing_item->item_id;
            }

            $warehouseReceiptDetail->item_id = $new_item_id;
          } else {
              $item = DB::table('items')->whereId($warehouseReceiptDetail->item_id)->first();
              $warehouseReceiptDetail->item_name = $item->name;
          }
      });
  }

  public function header()
  {
      return $this->belongsTo('App\Model\WarehouseReceipt','header_id','id');
  }
  public function piece()
  {
      return $this->belongsTo('App\Model\Piece','piece_id','id');
  }
  public function pallet()
  {
      return $this->belongsTo('App\Model\Item','pallet_id','id');
  }
  public function rack()
  {
      return $this->belongsTo('App\Model\Rack','rack_id','id');
  }
  public function vehicle_type()
  {
      return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
  }

  public static function update_item()
  {
      $items = DB::table('warehouse_receipt_details')
      ->whereRaw('item_id IS NULL')
      ->groupBy('item_name')
      ->select('id', 'item_name', 'item_id', 'long', 'wide', 'high', 'weight')
      ->get();

      foreach ($items as $item) {
        # code...
        if($item->item_id == null) {

            $item_order = Item::whereRaw('0 = 0')->count('id') + 1;
            $existing_item = WarehouseReceiptDetail::find($item->id)
            ->join('items', 'items.id', 'warehouse_receipt_details.item_id')
            ->where('item_name', $item->item_name)
            ->whereRaw('item_id IS NOT NULL')->first();
            if($existing_item == null) {

              $i = Item::create([
                'code' => DB::raw("(SELECT CONCAT('BRG', LPAD($item_order, 3, 0)))"),
                'name' => $item->item_name,
                'long' => $item->long ,
                'wide' => $item->wide ,
                'height' => $item->high ,
                'volume' => $item->long * $item->wide * $item->high ,
                'tonase' => $item->weight
              ]);
              $new_item_id = $i->id;
            }
            else {
              $new_item_id = $existing_item->item_id;
            }

            DB::table('warehouse_receipt_details')->whereRaw("item_name = '$item->item_name' AND item_id IS NULL")->update(['item_id' => $new_item_id]);
        }
      }
  }

}

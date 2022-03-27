<?php

namespace App\Model;
use App\Model\Rack;
use App\Model\Item;
use App\Model\StockTransactionReport;
use App\Abstracts\Inventory\WarehouseStockDetail AS WSD;
use Response;
use DB;
use Carbon\Carbon;
use Exception;

use Illuminate\Database\Eloquent\Model;

class WarehouseStockDetail extends Model
{
  protected $guarded = ['id'];

  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }

  public function rack()
  {
      return $this->belongsTo('App\Model\Rack','rack_id','id');
  }

  public function warehouse_receipt()
  {
      return $this->belongsTo('App\Model\WarehouseReceipt','no_surat_jalan','code');
  }

    public static function fetch_stocklist($request) {
        $item = WSD::stocklist($request->all());
        return $item;
    }

  public static function cek_kapasitas($unit = [], $option)
  {
      $racks = [];
      $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $option['warehouse_id']);
      $handling_is_exists = $handling_storage->count();
      if($handling_is_exists == 0) {
        $r = Rack::create([
          'warehouse_id' => $option['warehouse_id'],
          'barcode' => '-',
          'code' => 'Handling Area',
          'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
          'capacity_volume' => 100000,
          'capacity_tonase' => 100000
        ]);

        $handling_rack_id = $r->id;
      }
      else {
        $r = $handling_storage->selectRaw('racks.id AS id')->first();
        $handling_rack_id = $r->id;
      }
      // Memfilter rak yang digunakan
      foreach ($unit as $value) {
        # code...
        if (empty($value)) {
          continue;
        }
        if($value->storage_type == 'HANDLING') {
          $rack_id =$handling_rack_id;
          $value->rack_id = $rack_id;  
        }
        else {
          $rack_id = $value->rack_id;
        }

        $rack = Rack::find($rack_id);
        $rack->volume_sisa = $rack->capacity_volume - $rack->capacity_volume_used;
        $rack->tonase_sisa = $rack->capacity_tonase - $rack->capacity_tonase_used;
        $rack->message = '';
        $rack_is_exists = 0;
        foreach ($racks as $data) {
          if($data->id == $value->rack_id) {
            $rack_is_exists = 1;
          }
        }

        if($rack_is_exists == 0) {
          array_push($racks, $rack); 
        }
      }
      // Menghitung kapasitas yang digunakan
      $tidak_cukup = 0;
        foreach ($racks as $rack) {
          foreach ($unit as $value) {
            if(!isset($value)) {
              continue;
            }
            if($value->rack_id == $rack->id) {
                $value->qty = $value->qty ?? 0;
                $value->long = $value->long ?? 0;
                $value->wide = $value->wide ?? 0;
                $value->high = $value->high ?? 0;


                $value->qty = (float) $value->qty ?? 0;
                $value->long = (float) $value->long ?? 0;
                $value->wide = (float) $value->wide ?? 0;
                $value->high = (float) $value->high ?? 0;
                $volume_sisa = (float) ($value->qty * $value->long * $value->wide * $value->high) / 1000000;
                $tonase_sisa = $value->qty * $value->weight;

                $rack->volume_sisa = $rack->volume_sisa - $volume_sisa;
                $rack->tonase_sisa = $rack->tonase_sisa - $tonase_sisa;
              if($rack->volume_sisa < 0 || $rack->tonase_sisa < 0) {
                $tidak_cukup = 1;
                $message = "Barang melebihi kapasitas rak " . $rack->code;
              }
            }
          }
        }
      
      if($tidak_cukup == 1) {
            throw new Exception($message);
      }
      else {
        return true;
      }
  }
  public static function cek_pallet_keluar($unit = [], $option)
  {
      // Memfilter item


      $items = [];
      // Memfilter rak yang digunakan
      $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $option['warehouse_id']);
      $handling_is_exists = $handling_storage->count();
      $wd = null;
      if($handling_is_exists == 0) {
        $r = Rack::create([
          'warehouse_id' => $option['warehouse_id'],
          'barcode' => '-',
          'code' => 'Handling Area',
          'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
          'capacity_volume' => 100000,
          'capacity_tonase' => 100000
        ]);

        $handling_rack_id = $r->id;
      }
      else {
        $r = $handling_storage->selectRaw('racks.id AS id')->first();
        $handling_rack_id = $r->id;
      }
      foreach ($unit as $value) {
        # code...
        if (empty($value)) {
          continue;
        }
        if(!isset($value->pallet_id)) {
          continue;
        }
        $item_is_exists = 0;
        foreach ($items as $data) {
          if($data['pallet_id'] == $value->pallet_id) {
            $item_is_exists = 1;
          }
        }

        if($item_is_exists == 0) {
          $wd = DB::table('warehouse_stocks')
          ->where('warehouse_id', $option['warehouse_id'])
          ->where('item_id', $value->pallet_id)
          ->first();
          if($wd == null) {
               $data_pallet = DB::table('items')
               ->whereId($value->pallet_id)
               ->select('name')
               ->first();
               $data_warehouse = DB::table('warehouses')
               ->whereId($option['warehouse_id'])
               ->select('name')
               ->first();
               return Response::json(['message' => 'Pallet ' . $data_pallet->name . ' tidak ada di ' . $data_warehouse->name],422);
          }
          else {
            $item_data = Item::find($value->pallet_id);
            $item = [
              'pallet_id' => $value->pallet_id,
              'rack_id' => $value->rack_id,
              'stock' => isset($wd->qty) ? $wd->qty : 0,
              'item' => $item_data,
              'message' => ''
            ];
            array_push($items, $item); 
          }
        }
      }


      $wd = null;
      // Memvalidasi stok yang keluar
      $batal_keluar = 0;

      foreach ($items as $val) {
        foreach ($unit as $data) {
          // $val sama dengan barang
           if(!isset($data->pallet_id)) {
            continue;
          }
          if($val['pallet_id'] == $data->pallet_id && $val['rack_id'] == $data->rack_id) {
              if(empty($data->pallet_qty)) {
                $message = 'Jumlah pallet harus diisi';
                return Response::json(['message' => $message, 'items' => $items],422);
              }
              $val['stock'] = $val['stock'] - $data->pallet_qty;
          }

        }

        if($val['stock'] < 0) {
          $batal_keluar = 1;
          $message = "Pallet " . $val['item']->name . " yang akan dikeluarkan melebihi stok";
          $val['message'] = $message;
        }
      }        
      
      if($batal_keluar == 1) {
          throw new Exception($message);
      }
      else {
        return true;
      }
  }

}

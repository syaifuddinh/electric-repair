<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\WarehouseReceipt;
use App\Abstracts\PurchaseOrder;
use App\Abstracts\PurchaseOrderDetail;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Math;
use App\Abstracts\Setting\Unit;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Inventory\ItemCategory;
use App\Abstracts\Inventory\ItemMigration;
use App\Abstracts\Inventory\ItemMigrationReceipt;
use App\Abstracts\ReceiptType;
use App\Abstracts\Rack AS Bin;
use App\Model\Rack;
use App\Exports\Receipt\ImportItem;
use App\Imports\Receipt\WarehouseReceiptDetailImport;
use Excel;
use App\Abstracts\Setting\Imposition;
use App\Abstracts\Inventory\ReceiptQualityStatus;
use App\Abstracts\Sales\SalesOrderReturn;

class WarehouseReceiptDetail 
{
    protected static $table = 'warehouse_receipt_details';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {

        $request = self::fetchFilter($params);

        $dt = DB::table(self::$table);
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', self::$table . '.header_id');
        $dt = $dt->join('racks', 'racks.id', self::$table . '.rack_id');
        $dt = $dt->join('warehouses', 'warehouses.id', 'warehouse_receipts.warehouse_id');
        $dt = $dt->join('companies', 'companies.id', 'warehouse_receipts.company_id');
        $dt = $dt->leftJoin('items', 'items.id', 'warehouse_receipt_details.item_id');
        $dt = $dt->leftJoin('receipt_quality_statuses', 'receipt_quality_statuses.id', self::$table . '.receipt_quality_status');

        if($request['header_id']) {
            $dt = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['header_id'] = $args['header_id'] ?? null;

        return $params;
    }

    public static function index($header_id) {
        $params = [];
        $params['header_id'] = $header_id;
        $dt = self::query($params)
        ->select(self::$table . '.*')
        ->get();

        return $dt;
    }

    public static function checkTransferMutation($header_id) {
        $wr = WarehouseReceipt::show($header_id);
        if($wr->receipt_type_code == 'r03') {
            $units = self::index($header_id);
            $units->each(function($v) use($header_id) {
                self::validateQtyTransferMutation($header_id, $v->item_id, 0);
            });
        }
    }

    public static function getExisting($warehouse_receipt_id, $item_id) {
        $dt = DB::table(self::$table);
        $dt = $dt->where('header_id', $warehouse_receipt_id);  
        $dt = $dt->where('item_id', $item_id);  
        $qty = $dt->sum("qty");

        return $qty;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi batas dari purchase order
      Developer : Didin
      Status : Create
    */
    public static function validasiLimitPO($warehouse_receipt_id, $item_id, $qty) {
        $header = WarehouseReceipt::show($warehouse_receipt_id);     
        $type = ReceiptType::show($header->receipt_type_id);
        if($type && $type->code == "r01" && $item_id) {
            if($header->purchase_order_id) {
                $limit = PurchaseOrderDetail::getLimit($header->purchase_order_id, $item_id);
                $existing = self::getExisting($warehouse_receipt_id, $item_id);
                $qty += $existing;
                if($qty > $limit) {
                    $item = Item::show($item_id);
                    $po = PurchaseOrder::show($header->purchase_order_id);
                    $item_name = $item->name;
                    $po_code = $po->code;
                    $msg = "Item $item_name is exceed limit by purchase order #" . $po_code;
                    throw new Exception($msg);
                }
            }
        }
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = [], $id = null) {
        $params = [];
        if($id) {
            $detail = self::show($id);
            $warehouse_receipt_id = $detail->header_id;
        } else {
            $warehouse_receipt_id = $args['header_id'] ?? null;
            if($warehouse_receipt_id) {
                WarehouseReceipt::validate($warehouse_receipt_id);
            } else {
                throw new Exception('Warehouse receipt is required');
            }
        }
        $params['header_id'] = $warehouse_receipt_id;
        $header = WarehouseReceipt::show($warehouse_receipt_id);
        $handling_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $header->warehouse_id);
        $handling_is_exists = $handling_storage->count();
        if($handling_is_exists == 0) {
            $r = Rack::create([
              'warehouse_id' => $header->warehouse_id,
              'barcode' => '-',
              'code' => 'Handling Area',
              'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
              'capacity_volume' => 100000,
              'capacity_tonase' => 100000
            ]);

            $rack_id = $r->id;
        }
        else {
            $r = $handling_storage->selectRaw('racks.id AS id')->first();
            $rack_id = $r->id;
        }

        $params['storage_type'] = $args['storage_type'] ?? null;
        $params['rack_id'] = $args['rack_id'] ?? null;
        if($params['storage_type'] != 'RACK') {
            $params['rack_id'] = $rack_id;
        }
        $params['vehicle_type_id'] = $args['vehicle_type_id'] ?? null;
        $params['piece_id'] = $args['piece_id'] ?? null;
        $params['piece_id_2'] = $args['piece_id_2'] ?? null;
        if(!$params['piece_id_2']) {
            $params['piece_id_2'] = $params['piece_id'];
        }
        $params['qty_2'] = $args['qty_2'] ?? 0;
        $params['qty'] = $args['qty'] ?? 0;

        $params['qty'] = Math::adjustFloat($params['qty']);
        $params['qty_2'] = Math::adjustFloat($params['qty_2']);


        $params['weight'] = $args['weight'] ?? 0;
        $params['high'] = $args['high'] ?? 0;
        $params['kemasan'] = $args['kemasan'] ?? null;

        $params['long'] = Math::adjustFloat(($args['long'] ?? 0));
        $params['wide'] = Math::adjustFloat(($args['wide'] ?? 0));
        $params['high'] = Math::adjustFloat(($args['high'] ?? 0));
        $params['volume'] = Math::countVolume($params['long'], $params['wide'], $params['high']);

        $params['imposition'] = $args['imposition'] ?? null;
        if($header->receipt_type_code == "ro4") {
            if(!$params['imposition']) {

                throw new Exception("Imposition / charge in is required");
            }
        } else {
            $params['imposition'] = Imposition::getItem();
        }
        $params['is_exists'] = $args['is_exists'] ?? 0;
        $params['item_id'] = $args['item_id'] ?? null;
        $params['item_name'] = $args['item_name'] ?? null;
        if($params['item_id'] && !$params['item_name']) {
            $item = Item::show($params['item_id']);
            $params['item_name'] = $item->name;
        } 

        if($params['is_exists'] == -1) {
            $is_pallet = $args['is_pallet'] ?? null;
            $itemParams = [];
            $itemParams["name"] = $params['item_name'];
            if($is_pallet == 1) {
                $item_id = Item::storeAsPallet($itemParams);

            } else {
                $item_id = Item::storeWithDefault($itemParams);
            }
            $params['item_id'] = $item_id;
        }
        $params['is_use_pallet'] = $args['is_use_pallet'] ?? 0;
        if($params['is_use_pallet'] != 0) {
            $params['pallet_id'] = $args['pallet_id'] ?? null;
            $params['pallet_qty'] = $args['pallet_qty'] ?? null;
        } 
        // $params['item_id'] = $params['is_exists'] == 0 ? DB::raw('item_id') : $params['item_id'];

        $params['create_by'] = $args['create_by'] ?? auth()->id();

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('warehouse_receipt_details')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Warehouse receipt detail not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->where('warehouse_receipt_details.id', $id);
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $id) {
        $params["header_id"] = $id;
        $purchase_order_detail_id = $params['purchase_order_detail_id'] ?? null; 
        $insert = self::fetch($params);
        $insert['receipt_quality_status'] = ReceiptQualityStatus::getDraft();
        self::validasiLimitPO($id, $insert["item_id"], $insert["qty"]);
        $warehouse_receipt_detail_id = DB::table(self::$table)->insertGetId($insert);

        if($purchase_order_detail_id) {
            PurchaseOrderReceiptDetail::store($warehouse_receipt_detail_id, $purchase_order_detail_id);
        }

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        $request = self::fetch($params, $id);
        $dt = self::show($id);
        if($request['item_id'] && is_int($request['item_id'])) {
            if($dt->item_id != $request['item_id']) {
                $item = Item::show($request['item_id']);
                $request['item_name'] = $item->name;
            }
        }
        DB::table(self::$table)
        ->whereId($id)
        ->update($request);

        self::setNewItemId($id);
        SalesOrderReturn::validateLimitByWarehouseReceiptDetail($id);
    }

    /*
      Date : 29-08-2021
      Description : Set barang baru
      Developer : Didin
      Status : Create
    */
    public static function setNewItemId($id) {
        $warehouseReceiptDetail = self::show($id);
        if($warehouseReceiptDetail->item_id == null) {
            $item_order = DB::table('items')->count('id') + 1;
            $existing_item = DB::table('warehouse_receipt_details')->where('item_name', $warehouseReceiptDetail->item_name)->whereRaw('item_id IS NOT NULL')->first();
            if($existing_item == null) {
              $piece = DB::table('pieces')->whereName('Item')->first();
              $new_item_id = Item::store([
                'code' => DB::raw("(SELECT CONCAT('BRG', LPAD($item_order, 3, 0)))"),
                'name' => $warehouseReceiptDetail->item_name,
                'piece_id' => $piece->id,
                'long' => $warehouseReceiptDetail->long ,
                'wide' => $warehouseReceiptDetail->wide ,
                'height' => $warehouseReceiptDetail->high ,
                'tonase' => $warehouseReceiptDetail->weight
              ]);
            }
            else {
              $new_item_id = $existing_item->item_id;
            }

            DB::table('warehouse_receipt_details')->whereId($id)->update(['item_id' => $new_item_id]);
          } else {
              $item = DB::table('items')->whereId($warehouseReceiptDetail->item_id)->first();
              $warehouseReceiptDetail->item_name = $item->name;
          }
    }
    
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('warehouse_receipt_details')
        ->whereId($id)
        ->delete();
    }


    /*
      Date : 14-03-2021
      Description : Download format import
      Developer : Didin
      Status : Create
    */
    public static function downloadImportItemExample() {
        $columns = [];
        $columns[] = ['name' => 'item_name'];
        $columns[] = ['name' => 'qty'];
        $columns[] = ['name' => 'satuan'];
        $columns[] = ['name' => 'long'];
        $columns[] = ['name' => 'wide'];
        $columns[] = ['name' => 'high'];
        $columns[] = ['name' => 'weight'];

        return Excel::download(new ImportItem($columns), 'Format import item in receipt.xlsx');
    }


    /*
      Date : 14-03-2021
      Description : Import item
      Developer : Didin
      Status : Create
    */
    public static function importItem($file, $warehouse_id = null, $is_pallet = false) {
        if(!$file) {
            throw new Exception('File is required');
        }

        Checker::checkWarehouse($warehouse_id);

        $ext = $file->getClientOriginalExtension();
        if($ext != 'xls' && $ext != 'xlsx' ) {
            throw new Exception('Excel file is required');
        }
        $collection = (new WarehouseReceiptDetailImport)->toArray($file);
        $collection = $collection[0];
        $collection = array_splice($collection, 1, count($collection));
        $collection = collect($collection)
        ->filter(function($rows){
            $has_value = false;
            foreach ($rows as $idx => $row) {
                if($row !== '' && $row !== null) {
                    $has_value = true;
                }
            }
            return $has_value;
        })
        ->toArray();

        $res = self::adjustItemByExcel($collection, $is_pallet);
        $res = self::setSuggestionRack($res, $warehouse_id);

        return $res;
    }

    public static function adjustItemByExcel($items = [], $is_pallet = false) {
        $items = Setting::trimArray2D($items);
        $columns = [];
        $columns[] = ['name' => 'item_name'];
        $columns[] = ['name' => 'qty'];
        $columns[] = ['name' => 'satuan'];
        $columns[] = ['name' => 'long'];
        $columns[] = ['name' => 'wide'];
        $columns[] = ['name' => 'high'];
        $columns[] = ['name' => 'weight'];

        $params = [];
        foreach ($items as $item) {
            $param = [];
            foreach ($item as $i => $col) {
                if($i < count($columns)) {
                    $param[$columns[$i]['name']] = $col;
                }
            }
            $param = self::integrateExcelData($param, $is_pallet);
            $params[] = $param;
        }
        return $params;
    }

    public static function integrateExcelData($param = [], $is_pallet = false) {
        $item_name = $param['item_name'] ?? null;
        $satuan = $param['satuan'] ?? null;
        $param['kemasan'] = 'CASE';
        $param['imposition'] = 3;
        $param['imposition_name'] = 'Item';

        $satuanExist = Unit::validateByName($param['satuan']);
        if($satuanExist) {
            $unit = Unit::showByName($satuan);
            if($unit) {
                $param['piece_id'] = $unit->id;
            }
        } else {
            $insert = [];
            $insert['name'] = $param['satuan'];
            $param['piece_id'] = Unit::store($insert);
        }

        $itemExist = Item::validateByName($item_name);
        if($itemExist) {
            $item = Item::showByName($item_name);
            if($item) {
                $param['item_id'] = $item->id;
            }
        } else {
            $insert = [];
            $insert['tonase'] = $param['weight'] ?? 0;
            $insert['long'] = $param['long'] ?? 0;
            $insert['wide'] = $param['wide'] ?? 0;
            $insert['height'] = $param['high'] ?? 0;
            $insert['name'] = $param['item_name'];
            $insert['piece_id'] = $param['piece_id'];
            $insert['category_id'] = DB::table('categories')->first()->id;
            if($is_pallet) {
                $param['item_id'] = Item::storeAsPallet($insert);
            } else {
                $param['item_id'] = Item::storeWithDefault($insert);
            }

        }

        return $param;
    }

    /*
      Date : 05-03-2021
      Description : Menempatkan barang pada bin location
      Developer : Didin
      Status : Create
    */
    public static function setSuggestionRack($params = [], $warehouse_id) {
        $racks = Bin::index(['warehouse_id' => $warehouse_id]);
        if(count($racks) > 0) {
            $rack_id = $racks[0]->id;
            foreach ($params as $i => $param) {
                $dt = $params[$i];
                $item = Item::show($dt['item_id']);
                $rack = null;
                if($item->default_rack_id) {
                    $rack = Bin::show($item->default_rack_id);
                }
                if($rack && $rack->warehouse_id == $warehouse_id) {
                    $rack_id = $item->default_rack_id;
                } else {
                    if($item->category_id) {
                        $category = ItemCategory::show($item->category_id);
                        $rack = null;
                        if($category->default_rack_id) {
                            $rack = Bin::show($category->default_rack_id);
                        }
                        if($rack && $rack->warehouse_id == $warehouse_id) {
                            $rack_id = $category->default_rack_id;
                        } else {
                            if($category->parent_id) {
                                $parent = ItemCategory::show($category->parent_id);
                                $rack = null;
                                if($parent->default_rack_id) {
                                    $rack = Bin::show($parent->default_rack_id);
                                }
                                if($rack && $rack->warehouse_id == $warehouse_id) {
                                    $rack_id = $parent->default_rack_id;
                                }
                            }
                        }
                    }
                }



                $rack = Bin::show($rack_id);
                if($rack->warehouse_id == $warehouse_id) {
                    if($item->is_dangerous_good == 1) {
                        $racks = Bin::index([
                            'warehouse_id' => $warehouse_id, 
                            'is_dangerous_good' => 1
                        ]);
                        if(count($racks) > 0) {
                            $rack_id = $racks[0]->id;
                            $rack = Bin::show($rack_id);
                            $rack_name = $rack->name;
                            $params[$i]['rack_id'] = $rack_id;
                            $params[$i]['rack_name'] = $rack_name;
                            $params[$i]['storage_type'] = 'RACK';
                        }
                    } else {
                        $rack_name = $rack->name;
                        $params[$i]['rack_id'] = $rack_id;
                        $params[$i]['rack_name'] = $rack_name;
                        $params[$i]['storage_type'] = 'RACK';
                    }
                } else {
                    
                        $params[$i]['rack_name'] = 'Handling Area';
                        $params[$i]['storage_type'] = 'HANDLING';
                }
            }
        }

        return $params;
    }

    /*
      Date : 15-06-2021
      Description : Memvalidasi apakah barang telah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApprovedQuality($id) {
        $dt = self::show($id);
        $status = ReceiptQualityStatus::getApproved();
        if($dt->receipt_quality_status == $status) {
            $statusData = ReceiptQualityStatus::show($status);
            $msg = 'Data was ' . $statusData->name;
            throw new Exception($msg);
        }
    }

    /*
      Date : 15-06-2021
      Description : Memvalidasi apakah barang telah di-reject atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsRejectedQuality($id) {
        $dt = self::show($id);
        $status = ReceiptQualityStatus::getRejected();
        if($dt->receipt_quality_status == $status) {
            $statusData = ReceiptQualityStatus::show($status);
            $msg = 'Data was ' . $statusData->name;
            throw new Exception($msg);
        }
    }

    /*
      Date : 15-06-2021
      Description : Menyetujui quality check
      Developer : Didin
      Status : Create
    */
    public static function approveQuality($id) {
        self::validateIsApprovedQuality($id);
        self::validateIsRejectedQuality($id);
        $status = ReceiptQualityStatus::getApproved();
        DB::table(self::$table)
        ->whereId($id)
        ->update([
            'receipt_quality_status' => $status
        ]);
    }

    /*
      Date : 15-06-2021
      Description : Menolak quality check
      Developer : Didin
      Status : Create
    */
    public static function rejectQuality($id) {
        self::validateIsApprovedQuality($id);
        self::validateIsRejectedQuality($id);
        $status = ReceiptQualityStatus::getRejected();
        DB::table(self::$table)
        ->whereId($id)
        ->update([
            'receipt_quality_status' => $status
        ]);
    }

    /*
      Date : 15-06-2021
      Description : Memvalidasi jumlah barang, apakah melebihi batas mutasi transfer atau tidak 
      Developer : Didin
      Status : Create
    */
    public static function validateQtyTransferMutation($warehouse_receipt_id, $item_id, $qty = 0) {
        $header = WarehouseReceipt::show($warehouse_receipt_id);     
        $type = ReceiptType::show($header->receipt_type_id);
        if($type && $type->code == "r03" && $item_id) {
            $received = ItemMigrationReceipt::getReceived($warehouse_receipt_id, $item_id);
            $received += $qty;
            $requested = ItemMigrationReceipt::getRequested($warehouse_receipt_id, $item_id);

            if($received > $requested) {
                $item = Item::show($item_id);
                $item_name = $item->name;
                $code = null;
                $im = ItemMigration::showByWarehouseReceipt($warehouse_receipt_id);
                if($im) {
                    $code = $im->code;
                }
                $msg = "Item $item_name is exceed limit by item migration #" . $code;
                throw new Exception($msg);
            }
        }
    }

    /*
      Date : 15-06-2021
      Description : Mendapatkan barcode 
      Developer : Didin
      Status : Create
    */
    public static function getBarcode($id) {
        self::validate($id);
        $r = str_pad($id, 6, '0', STR_PAD_LEFT);

        return $r;
    }
}


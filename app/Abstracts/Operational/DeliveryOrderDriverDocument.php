<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Setting\JobStatus;
use App\Abstracts\Operational\DeliveryOrderDriver;
use Image;

class DeliveryOrderDriverDocument extends DeliveryOrderDriver
{
    protected static $table = 'delivery_order_driver_documents';

    
    public static function fetchFilter($args = []) {
        $params = [];
        $params['delivery_order_driver_id'] = $args['delivery_order_driver_id'] ?? null;

        return $params;
    }

    public static function query($params = []) {
        $request = self::fetchFilter($params);

        $dt = DB::table(self::$table);

        if($request['delivery_order_driver_id']) {
            $dt = $dt->where(self::$table . '.delivery_order_driver_id', $request['delivery_order_driver_id']);
        }

        return $dt;
    }

    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    public static function index($delivery_order_driver_id) {
        $dt = self::query(['delivery_order_driver_id' => $delivery_order_driver_id]);
        $url = url('/files');
        $dt = $dt->select(
            self::$table . '.id',
            DB::raw('CONCAT("' . $url  . '/", ' . self::$table . '.file) AS url'),
            self::$table . '.filename'
        );
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 19-02-2021
      Description : Menampilkan detail surat jalan driver
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    public static function store($delivery_order_driver_id, $file) {
        
        parent::validate($delivery_order_driver_id);
        if(!$file) {
            throw new Exception('File is required in delivery order driver document');
        }


        $filepath = Carbon::now()->format('YmdHis') . str_random(10) . $file->getClientOriginalName();

        $filename = $file->getClientOriginalName();

        Image::make( $file->getRealPath() )->save(public_path('files/' . $filepath));

        DB::table(self::$table)
        ->insert([
            'delivery_order_driver_id' => $delivery_order_driver_id,
            'file' => $filepath,
            'filename' => $filename
        ]);
    }
}

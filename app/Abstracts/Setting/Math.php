<?php

namespace App\Abstracts\Setting;

use DB;
use Exception;

class Math 
{
    protected static $phi = 3.14;

    /*
      Date : 29-08-2020
      Description : Pembulatan 3 angka
      Developer : Didin
      Status : Create
    */
    public static function adjustFloat($value) {
        $dt = round($value ?? 0, 3);

        return $dt;
    }
    /*
      Date : 29-08-2020
      Description : Menghitung persen dari sebuah nominal
      Developer : Didin
      Status : Create
    */
    public static function countPercent($value, $percent) {
        $dt = $value * $percent / 100;
        $dt = round($dt, 3);

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung volume dalam satuan m3
      Developer : Didin
      Status : Create
      Parameter : long, wide dan height, dalam satuan cm
    */
    public static function countVolume($long, $wide, $height) {
        $volumeInCm = $long * $wide * $height;
        $volume = $volumeInCm / 1000000;
        $volume = round($volume, 3);

        return $volume;
    }

    /*
      Date : 29-08-2020
      Description : Menghitung total harga berdasarkan pengenaan 
      Developer : Didin
      Status : Create
    */
    public static function countItemPrice($params = []) {
        $imposition = $params['imposition'];
        $qty = $params['qty'] ?? 0;
        $volume = $params['volume'] ?? 0;
        $weight = $params['weight'] ?? 0;
        $price_item = $params['price_item'] ?? 0;
        $price_volume = $params['price_volume'] ?? 0;
        $price_tonase = $params['price_tonase'] ?? 0;

        $min_item = $params['min_item'] ?? 0;
        $min_volume = $params['min_volume'] ?? 0;
        $min_tonase = $params['min_tonase'] ?? 0;

        $total_item = $params['total_item'] ?? 0;
        $total_volume = $params['total_volume'] ?? 0;
        $total_tonase = $params['total_tonase'] ?? 0;
        $res = 0;
        if($imposition == 1) {
            $res = $volume * $price_volume;
            if($min_volume > $total_volume) {
                $res *= ($min_volume / $total_volume);
            } 
        } else if($imposition == 2) {
            $res = $weight * $price_tonase;
            if($min_tonase > $total_tonase) {
                $res *= ($min_tonase / $total_tonase);
            } 
        } else if($imposition == 3) {
            $res = $qty * $price_item;
            if($min_item > $total_item) {
                $res *= ($min_item / $total_item);
            } 
        }
        $res = round($res);

        return $res;
    }

    /*
      Date : 29-08-2020
      Description : Mengambil harga satuan berdasarkan pengenaan 
      Developer : Didin
      Status : Create
    */
    public static function getItemPrice($params = []) {
        $imposition = $params['imposition'];
        $price_item = $params['price_item'] ?? 0;
        $price_volume = $params['price_volume'] ?? 0;
        $price_tonase = $params['price_tonase'] ?? 0;

        $res = 0;
        if($imposition == 1) {
            $res = $price_volume;
        } else if($imposition == 2) {
            $res = $price_tonase;
        } else if($imposition == 3) {
            $res = $price_item;
        }

        return $res;
    }

    /*
      Date : 29-08-2020
      Description : Mencari tahu apakah barang kurang dari minimum atau lebih dari minimum 
      Developer : Didin
      Status : Create
    */
    public static function isChargeInMinimum($params = []) {
        $imposition = $params['imposition'];
        $qty = $params['total_item'] ?? 0;
        $volume = $params['total_volume'] ?? 0;
        $weight = $params['total_tonase'] ?? 0;

        $min_item = $params['min_item'] ?? 0;
        $min_volume = $params['min_volume'] ?? 0;
        $min_tonase = $params['min_tonase'] ?? 0;

        $res = false;
        if($imposition == 1) {
            if($volume < $min_volume) {
                $res = true;
            }
        } else if($imposition == 2) {
            if($weight < $min_tonase) {
                $res = true;
            }
        } else if($imposition == 3) {
            if($qty < $min_item) {
                $res = true;
            }
        }

        return $res;
    }

    public static function getDistance($lat1, $lon1, $lat2, $lon2) {
        $r = 6371000;
        $sin1 = $lat1 * self::$phi / 180; // φ, λ in radians
        $v1 = $lat2 * self::$phi / 180;
        $v3 = ($lat2-$lat1) * self::$phi / 180;
        $v4 = ($lon2-$lon1) * self::$phi / 180;

        $a = sin($v3/2) * sin($v3/2) +
                  cos($sin1) * cos($v1) *
                  sin($v4/2) * sin($v4/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $d = $r * $c; // in metres

        return $d;
    }
}

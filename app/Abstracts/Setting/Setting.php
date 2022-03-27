<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class Setting 
{
    /*
      Date : 05-03-2021
      Description : Mengambil data setting
      Developer : Didin
      Status : Create
    */
    public static function fetch($slug, $key) {
        $settings = DB::table('settings');
        $settings = $settings->whereSlug($slug);
        $settings = $settings->first();
        if($settings != null) {
            $content = json_decode($settings->content);
            $settings = $content->settings;
            foreach($settings as $s) {
                if($s->slug == $key) {
                    return $s;
                }
            }
        }

        return false;
    }

    public static function fetchValue($slug, $key) {
        $dt = self::fetch($slug, $key);
        $val = $dt->value ?? null;

        return $val;
    }

    public static function trimArray2D($lists) {
        if(count($lists) > 0) {
            $test = count($lists[0]) - 1;
        }
        $i = 0;
        foreach($lists as $list) {
            $empty = 0;
            for($x = 0;$x < $test;$x++) {
                $list[$x] = trim($list[$x]);
                $list[$x] = str_replace(' ', '', $list[$x]);
                if(!$list[$x] OR $list[$x] === ' ') {
                    $empty++;
                }
            }

            if($empty < $test - 2) {
                $i++;
            } else {
                array_splice($lists, $i, 1);
            }
        }

        return $lists;
    }
}

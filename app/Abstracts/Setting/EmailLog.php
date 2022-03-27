<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class EmailLog 
{
    public static function query() {
        $dt = DB::table('email_logs');

        return $dt;
    }

    public static function fetch($args = []) {
        $params = [];
        $params['subject'] = $args['subject'] ?? null;
        $params['destination'] = $args['destination'] ?? null;
        $params['body'] = $args['body'] ?? null;
        $params['status'] = $args['status'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['created_at'] = Carbon::now();
        $params['created_by'] = $args['created_by'] ?? auth()->id();
        
        return $params;
    }

    public static function store($params = []) {
        $params = self::fetch($params);
        self::query()->insert($params);
    }
}

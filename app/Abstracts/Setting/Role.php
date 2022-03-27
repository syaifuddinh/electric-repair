<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\User;

class Role 
{
    public static $table = 'roles';
    public static $min_deep = '1';
    public static $max_deep = '6';

    public static function userRoleQuery($user_id) {

        $user = User::show($user_id);
        if($user->is_admin) {
            $dt = DB::table(self::$table);
            $dt = $dt->select(
                'id AS role_id',
                DB::raw($user_id . ' AS user_id')
            );
        } else {
            $dt = DB::table('user_roles');
            $dt = $dt->where('user_id', $user_id);
            $dt = $dt->select('user_id', 'role_id');
        }

        $dt = DB::query()->fromSub($dt, 'user_roles');

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function index($user_id) {
        $items = [];
        $table = '';
        $user_roles = self::userRoleQuery($user_id);
        for($i = self::$min_deep;$i <= self::$max_deep;$i++) {
            $table_name = 'item' .  $i;
            $table = self::$table . ' AS ' . $table_name;
            $item = DB::table($table);
            $item = $item->where('deep', $i);
            $item = $item->orderBy($table_name . '.urut');
            $item = $item->leftJoinSub($user_roles, 'user_roles', function($query) use ($table_name){
                $query->on($table_name . '.id', 'user_roles.role_id');
            });
            $item = $item->select(
                $table_name . '.id',
                DB::raw("IF(user_roles.user_id IS NULL, 0, 1) AS include")
            );
            if($i > self::$min_deep) {
                $item = $item->addSelect([
                    $table_name . '.parent_id'
                ]);
                $item = $item->groupBy($table_name . '.parent_id');
            } else {
                $item = $item->addSelect([
                    $table_name . '.name',
                    $table_name . '.deep'
                ]);                
            }

            $items[] = [
                'item' => $item,
                'name' => $table_name
            ];
        }

        foreach ($items as $i => $item) {
            $additional = '';
            if($i < count($items) - 1) {
                $next = $items[$i + 1];
            }

            if($i > 0) {
                $origin_table = $item['name'];

                if($i < count($items) - 1) {
                    $next_table = $next['name'];
                    $additional = ", 'roles', $next_table.roles";
                }

                $item['item'] = $item['item']->addSelect([
                    DB::raw("JSON_ARRAYAGG(JSON_OBJECT('id', $origin_table.id, 'name', $origin_table.name,'deep', $origin_table.deep, 'include', IF(user_roles.user_id IS NULL, 0, 1), 'slug', $origin_table.slug $additional)) AS `roles`")
                ]);
            }

            if($i == 0) {
                $item['item'] = $item['item']->addSelect([
                    $next['name'] . '.roles'
                ]);                
            }
        }

        $dt = null;
        $items = array_reverse($items);
        $length = count($items);
        foreach ($items as $i => $item) {
            $next = null;
            $next2 = null;
            if($i < $length - 1) {
                $next = $items[$i + 1];
                $dt = $next['item'];
            }

            if($i < count($items) - 1) {
                $origin_table = $next['name'];
                $destination_table = $item['name'];
                $unit  = $item['item'];
                
                $dt = $dt->leftJoinSub($unit,  $destination_table, function($query) use ($destination_table, $origin_table){
                    $query->on(
                        $destination_table . '.parent_id', 
                        $origin_table . '.id'
                    );
                });

            } 
        }


        // dd($dt->toSql());

        $dt = $dt->get()->map(function($v){
            $v->roles = json_decode($v->roles);

            return $v;
        });


        return $dt;
    }

}

<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class User
{
    protected static $table = 'users';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('contacts', 'contacts.id', 'users.contact_id');

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query()
        ->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*', 'contacts.address');
        $dt = $dt->first();

        return $dt;
    }


    /*
      Date : 29-08-2020
      Description : Mengganti password user
      Developer : Didin
      Status : Create
    */
    public static function changePassword($new_password, $confirm_password, $id) {
        if(!$new_password) {
            throw new Exception('New password is required');
        }
        if(!$confirm_password) {
            throw new Exception('Confirm password is required');
        }
        if($new_password != $confirm_password) {
            throw new Exception('New password and confirm password must be same');
        }

        self::validate($id);
        DB::table('users')->whereId($id)->update([
            'password' => bcrypt($new_password)
        ]);
    }
    
    
    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }
}

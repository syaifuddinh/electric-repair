<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Setting\Setting;
use DB;
use Exception;

class SettingController extends Controller
{
    /*
      Date : 12-08-2020
      Description : Menampilkan setting
      Developer : Didin
      Status : Create
    */
    public function index($slug = '') {
        $settings = DB::table('settings');
        if($slug) {
            $settings->whereSlug($slug);
        }
        $settings = $settings->get()->map(function($e){
            $e->content = json_decode($e->content);
            return $e;
        });
        return response()->json($settings);
    }

    /*
      Date : 12-08-2020
      Description : Menampilkan detail setting
      Developer : Didin
      Status : Create
    */
     public static function fetch($slug, $key) {
        $dt = Setting::fetch($slug, $key);

        return $dt;
     }

    public function show($slug, $key) {
        $resp = $this->fetch($slug, $key);
        if(!$resp) {
            return response()->json(['message' => 'Not Found'], 404);
        } else {
            return response()->json($resp);          
        }
    }

    /*
      Date : 12-08-2020
      Description : Meng-update setting
      Developer : Didin
      Status : Create
    */
    public function update(Request $request) {
        $params = collect($request->all());
        DB::beginTransaction();
        try {
            $params->map(function($e){
                $existing = DB::table('settings')
                ->whereSlug($e['slug'] ?? '')
                ->count('id');

                if($existing > 0) {
                    DB::table('settings')
                    ->whereSlug($e['slug'])              
                    ->update([
                        'content' => json_encode($e['content'])
                    ]);
                }
            });
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }
        return response()->json(['message' => 'Data berhasil di-update']);
    }
}

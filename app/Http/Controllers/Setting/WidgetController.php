<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
use Exception;

class WidgetController extends Controller
{
      /*
        Date : 03-04-2020
        Description : Menampilkan daftar widget
        Developer : Didin
        Status : Create
      */
      public function index() {
            $queries = DB::table('widget')
            ->select('id', 'name')
            ->get();

            return $queries;
      }

      /*
        Date : 03-04-2020
        Description : Menyimpan widget
        Developer : Didin
        Status : Create
      */
      public function store(Request $request) {
          $request->validate([
              'name' => 'required',
              'query_id' => 'required',
              'type' => 'required',
          ], [
              'name.required' => 'Nama tidak boleh kosong',
              'query_id.required' => 'Query tidak boleh kosong',
              'type.required' => 'Tipe tidak boleh kosong'
          ]);

          if(($request->input('width') ?? 1) > 12) {
              return Response::json(['message' => 'Lebar tidak boleh bernilai lebih dari 12'], 421);
          }
          if(($request->input('width') ?? 1) < 1) {
              return Response::json(['message' => 'Lebar tidak boleh bernilai kurang dari 0'], 421);
          }

          DB::table('widgets')
          ->insert([
              'name' => $request->input('name'),
              'query_id' => $request->input('query_id'),
              'type' => $request->input('type'),
              'width' => $request->input('width') ?? 1,
          ]);

          return Response::json(['message' => 'Data berhasil diinput']);
      }

      /*
        Date : 03-04-2020
        Description : meng-update widget
        Developer : Didin
        Status : Create
      */
      public function update(Request $request, $id) {
          $request->validate([
              'name' => 'required',
              'query_id' => 'required',
              'type' => 'required',
          ], [
              'name.required' => 'Nama tidak boleh kosong',
              'query_id.required' => 'Query tidak boleh kosong',
              'type.required' => 'Tipe tidak boleh kosong'
          ]);

          if(($request->input('width') ?? 1) > 12) {
              return Response::json(['message' => 'Lebar tidak boleh bernilai lebih dari 12'], 421);
          }
          if(($request->input('width') ?? 1) < 1) {
              return Response::json(['message' => 'Lebar tidak boleh bernilai kurang dari 0'], 421);
          }

          $count = DB::table('widgets')
          ->whereId($id)
          ->count('id');
          if($count < 1) {
              return Response::json(['message' => 'Data tidak ditemukan'], 404);            
          } 

          DB::table('widgets')
          ->whereId($id)
          ->update([
              'name' => $request->input('name'),
              'query_id' => $request->input('query_id'),
              'type' => $request->input('type'),
              'width' => $request->input('width') ?? 1,
          ]);

          return Response::json(['message' => 'Data berhasil diupdate']);
      }

      /*
        Date : 03-04-2020
        Description : Menampilkan detail widget
        Developer : Didin
        Status : Create
      */
      public function show($id) {
          $count = DB::table('widgets')
          ->whereId($id)
          ->count('id');
          if($count == 0) {
              return Response::json(['message' => 'Data tidak ditemukan'], 421);
          }

          $widget = DB::table('widgets')
          ->whereId($id)
          ->first();

          return Response::json($widget, 200, [], JSON_NUMERIC_CHECK);

      }
      /*
        Date : 03-04-2020
        Description : Menghapus widget
        Developer : Didin
        Status : Create
      */
      public function destroy($id) {
          $count = DB::table('widgets')
          ->whereId($id)
          ->count('id');
          if($count == 0) {
              return Response::json(['message' => 'Data tidak ditemukan'], 421);
          }

          DB::table('dashboard_details')
          ->where('widget_id', $id)
          ->delete();

          DB::table('widgets')
          ->whereId($id)
          ->delete();

          return Response::json(['message' => 'Data berhasil dihapus']);
      }
}

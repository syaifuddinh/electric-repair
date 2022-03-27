<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
use Exception;

class DashboardController extends Controller
{
      /*
        Date : 03-04-2020
        Description : Menampilkan daftar dashboard
        Developer : Didin
        Status : Create
      */
      public function index() {
            $queries = DB::table('dashboards')
            ->select('id', 'name')
            ->get();

            return $queries;
      }

      /*
        Date : 03-04-2020
        Description : Menyimpan dashboard
        Developer : Didin
        Status : Create
      */
      public function store(Request $request) {
          $request->validate([
              'name' => 'required',
              'role_id' => 'required',
              'detail' => 'required',
          ], [
              'name.required' => 'Nama tidak boleh kosong',
              'role_id.required' => 'Hak akses tidak boleh kosong',
              'detail.required' => 'Detail tidak boleh kosong'
          ]);

          $dashboard_id = DB::table('dashboards')
          ->insertGetId([
              'name' => $request->input('name'),
              'role_id' => $request->input('role_id')
          ]);

          $detail = ($request->detail ?? []);
          foreach ($detail as $unit) {
              if(null != ($unit['widget_id'] ?? null)) {
                  DB::table('dashboard_details')
                  ->insert([
                      'header_id' => $dashboard_id,
                      'widget_id' => $unit['widget_id'],
                      'row' => $unit['row']
                  ]);
              }
          }

          return Response::json(['message' => 'Data berhasil diinput']);
      }

      /*
        Date : 03-04-2020
        Description : meng-update dashboard
        Developer : Didin
        Status : Create
      */
      public function update(Request $request, $id) {
          $request->validate([
              'name' => 'required',
              'role_id' => 'required',
              'detail' => 'required',
          ], [
              'name.required' => 'Nama tidak boleh kosong',
              'role_id.required' => 'Hak akses tidak boleh kosong',
              'detail.required' => 'Detail tidak boleh kosong'
          ]);

          DB::table('dashboards')
          ->whereId($id)
          ->update([
              'name' => $request->input('name'),
              'role_id' => $request->input('role_id')
          ]);

          DB::table('dashboard_details')
          ->whereHeaderId($id)
          ->delete();

          $detail = ($request->detail ?? []);
          foreach ($detail as $unit) {
              if(null != ($unit['widget_id'] ?? null)) {
                  DB::table('dashboard_details')
                  ->insert([
                      'header_id' => $id,
                      'widget_id' => $unit['widget_id'],
                      'row' => $unit['row']
                  ]);
              }
          }

          return Response::json(['message' => 'Data berhasil diupdate']);
      }

      /*
        Date : 03-04-2020
        Description : Menampilkan detail dashboard
        Developer : Didin
        Status : Create
      */
      public function show($id) {
          $count = DB::table('dashboards')
          ->whereId($id)
          ->count('id');
          if($count == 0) {
              return Response::json(['message' => 'Data tidak ditemukan'], 421);
          }

          $dashboard = DB::table('dashboards AS D')
          ->join('roles AS R', 'R.id', 'D.role_id')
          ->where('D.id', $id)
          ->select('D.id', 'D.role_id', 'D.name', 'R.name AS role_name')
          ->first();

          return Response::json($dashboard, 200, [], JSON_NUMERIC_CHECK);

      }
      /*
        Date : 03-04-2020
        Description : Menampilkan detail widget pada dashboard
        Developer : Didin
        Status : Create
      */
      public function showDetail($id) {

          $result = DB::table('dashboard_details AS D')
          ->join('widgets AS W', 'W.id', 'D.widget_id')
          ->join('queries AS Q', 'W.query_id', 'Q.id')
        ->where('D.header_id', $id)
          ->select('D.id', 'W.name AS widget_name', 'D.row', 'W.id AS widget_id', 'W.width', 'Q.query', 'W.type', 'W.width', 'Q.name AS query_name')
          ->orderBy('D.row', 'ASC')
          ->get();

          return Response::json($result, 200, [], JSON_NUMERIC_CHECK);

      }
      /*
        Date : 03-04-2020
        Description : Menghapus dashboard
        Developer : Didin
        Status : Create
      */
      public function destroy($id) {
          $count = DB::table('dashboards')
          ->whereId($id)
          ->count('id');
          if($count == 0) {
              return Response::json(['message' => 'Data tidak ditemukan'], 421);
          }

          DB::table('dashboards')
          ->whereId($id)
          ->delete();

          return Response::json(['message' => 'Data berhasil dihapus']);
      }
}

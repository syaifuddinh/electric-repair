<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
use Exception;

class QueryController extends Controller
{
      public function index() {
            $queries = DB::table('queries')
            ->select('id', 'name', 'query')
            ->get();

            return Response::json($queries, 200, [], JSON_NUMERIC_CHECK);
      }

      public function showTable() {
            $tables = DB::select('SHOW TABLES');
            return $tables;
      }

      public function detailTable(Request $request) {
            $data = DB::table($request->input('table'))
            ->get();
            return $data;
      }

      public function run(Request $request) {
            if(!$request->filled('query')) {
                return Response::json(['message' => 'Query tidak boleh kosong'], 421);
            }
            $query = trim($request->input('query'));
            $words = explode(' ', $query);
            if(strtolower($words[0]) != 'select') {
                return Response::json(['message' => 'Only select statement is allowed !'], 421);
            }

            try {
                $result = DB::select($request->input('query'));
            } catch(Exception $e) {
                return Response::json(['message' => $e->getMessage()], 421);
            }

            return $result;
      }

      public function first(Request $request) {
            if(!$request->filled('query')) {
                return Response::json(['message' => 'Query tidak boleh kosong'], 421);
            }
            $query = trim($request->input('query'));
            $words = explode(' ', $query);
            if(strtolower($words[0]) != 'select') {
                return Response::json(['message' => 'Only select statement is allowed !'], 421);
            }

            try {
                $result = DB::select($request->input('query'));
            } catch(Exception $e) {
                return Response::json(['message' => $e->getMessage()], 421);
            }

            return $result[0];
      }

      public function store(Request $request) {
          $request->validate([
              'name' => 'required',
              'query' => 'required'
          ], [
              'name.required' => 'Nama tidak boleh kosong',
              'query.required' => 'Query tidak boleh kosong'
          ]);

          DB::table('queries')
          ->insert([
              'query' => $request->input('query'),
              'name' => $request->input('name')
          ]);

          return Response::json(['message' => 'Data berhasil diinput']);
      }

      public function update(Request $request, $id) {
          $request->validate([
              'query' => 'required'
          ], [
              'query.required' => 'Query tidak boleh kosong'
          ]);

          $data = DB::table('queries')
          ->whereId($id)
          ->first();

          if($data == null) {
              return Response::json(['message' => 'Data tidak ditemukan'], 404);
          }

          DB::table('queries')
          ->whereId($id)
          ->update([
              'query' => $request->input('query'),
          ]);

          return Response::json(['message' => 'Data berhasil diupdate']);
      }
}

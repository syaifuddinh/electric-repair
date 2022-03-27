<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Abstracts\Setting\TypeTransaction;

class AdditionalField
{
    /*
      Date : 12-02-2021
      Description : Menangkap parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params = [];
        $params['name'] = $args['name'];
        if($params['name']) {
            $slug = Str::studly($params['name']);
            $params['slug'] = preg_replace('/[^a-zA-Z0-9]/', '', $slug);
        }
        $params['type_transaction_id'] = $args['type_transaction_id'];
        $params['field_type_id'] = $args['field_type_id'];
        $params['show_in_job_order_summary'] = $args['show_in_job_order_summary'] ?? 0;
        $params['show_in_index'] = $args['show_in_index'] ?? 0;
        $params['show_in_operational_progress'] = $args['show_in_operational_progress'] ?? 0;
        $params['show_in_manifest'] = $args['show_in_manifest'] ?? 0;

        return $params;
    }

    public static function store($args = []) {
        $params = self::fetch($args);
        DB::table('additional_fields')
        ->insert($params);
    }

    public static function update($args = [], $id) {
        $dt = self::show($id);
        $old_field = $dt->slug;
        $params = self::fetch($args);
        DB::table('additional_fields')
        ->whereId($id)
        ->update($params);
        self::adjust($old_field, $id);

    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberaraan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('additional_fields')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    public static function show($id) {
        self::validate($id);
        $dt = DB::table('additional_fields')
        ->whereId($id);
        
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan fitur
      Developer : Didin
      Status : Create
    */
    public static function indexGroup() {
        $dt = DB::table('type_transactions')
        ->where('type_transactions.is_customable_field', 1)
        ->select('id', 'name')
        ->get();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Meng-query kan data additional field
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('additional_fields');
        $dt = $dt->join('field_types', 'field_types.id', 'additional_fields.field_type_id');
        $dt = $dt->join('type_transactions', 'type_transactions.id', 'additional_fields.type_transaction_id');

        return $dt;
    }

    public static function indexKey($type_transaction, $params = []) {
        $dt = self::query();
        $dt = $dt->where('type_transactions.slug', $type_transaction);

        $show_in_job_order_summary = $params['show_in_job_order_summary'] ?? null;
        if($show_in_job_order_summary) {
            $dt = $dt->where('show_in_job_order_summary', $show_in_job_order_summary);
        }

        $show_in_operational_progress = $params['show_in_operational_progress'] ?? null;
        if($show_in_operational_progress) {
            $dt = $dt->where('show_in_operational_progress', $show_in_operational_progress);
        }
        
        $show_in_manifest = $params['show_in_manifest'] ?? null;
        if($show_in_manifest) {
            $dt = $dt->where('show_in_manifest', $show_in_manifest);
        }
        
        $show_in_index = $params['show_in_index'] ?? null;
        if($show_in_index) {
            $dt = $dt->where('show_in_index', $show_in_index);
        }

        $dt = $dt->select('additional_fields.slug')->get();
        $dt = $dt->pluck('slug');

        return $dt;
    }

    public static function indexByTransaction($type_transaction, $params = []) {
        $dt = self::query();
        $dt = $dt->where('type_transactions.slug', $type_transaction);

        $show_in_job_order_summary = $params['show_in_job_order_summary'] ?? null;
        if($show_in_job_order_summary) {
            $dt = $dt->where('show_in_job_order_summary', $show_in_job_order_summary);
        }

        $show_in_operational_progress = $params['show_in_operational_progress'] ?? null;
        if($show_in_operational_progress) {
            $dt = $dt->where('show_in_operational_progress', $show_in_operational_progress);
        }

        $show_in_manifest = $params['show_in_manifest'] ?? null;
        if($show_in_manifest) {
            $dt = $dt->where('show_in_manifest', $show_in_manifest);
        }

        $show_in_index = $params['show_in_index'] ?? null;
        if($show_in_index) {
            $dt = $dt->where('show_in_index', $show_in_index);
        }

        $dt = $dt->select('additional_fields.slug', 'field_types.slug AS type_field', 'additional_fields.name');
        $dt = $dt->get();

        return $dt;
    }    

    /*
      Date : 12-02-2021
      Description : Menghapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::beginTransaction();
        DB::table('additional_fields')
        ->whereId($id)
        ->delete();

        DB::commit();
    }

    public static function adjust($old_field, $id) {
        $additional_field = self::show($id);
        $dt = TypeTransaction::show($additional_field->type_transaction_id);
        if($dt->table_name) {
            $new_field = $additional_field->slug;
            DB::table($dt->table_name)
            ->update([
                'additional' => DB::raw("REPLACE(additional, '$old_field', '$new_field')")
            ]);
        }

    }
}

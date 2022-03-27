<?php

namespace App\Http\Controllers\Api\v4\Contact;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DataTables;
use Response;
use Carbon\Carbon;
use DB;

class ContactApiController extends Controller
{
    /*
      Date : 15-04-2020
      Description : Menampilkan daftar kontak
      Developer : Didin
      Status : Create
  */
    public function contactDatatable(Request $request)
    {
        $item = DB::table('contacts');
        return DataTables::of($item)
        ->filter(function ($query ) use ($request ){
            if($request->filled('name')) {
                $request->name = addslashes($request->name);
                $query->whereRaw("name LIKE '%{$request->name}%'");
            }

            $query
            ->select('contacts.id', 'contacts.name', 'contacts.address')
            ->offset($request->start ?? 0)
            ->limit($request->length ?? 1000000);
        })
        ->make(true);
    }

}

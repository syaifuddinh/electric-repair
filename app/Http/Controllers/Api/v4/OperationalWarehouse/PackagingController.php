<?php
namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use Carbon\Carbon;
use App\Abstracts\Inventory\Packaging;
use App\Abstracts\Inventory\PackagingOldItem;
use App\Abstracts\Inventory\PackagingNewItem;
use Exception;
use DB;

class PackagingController extends Controller
{
    /*
      Date : 16-03-2021
      Description : Menyimpan data packaging
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required'
        ]);
        $status_code = 200;
        $msg = 'OK';

        DB::beginTransaction();
        try {
            $id = Packaging::store($request->all());
            PackagingOldItem::storeMultiple($request->old_items, $id);
            PackagingNewItem::storeMultiple($request->new_items, $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            $status_code = 421;
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 17-03-2021
      Description : Menyimpan detail data
      Developer : Didin
      Status : Create
    */
    public function show($id) {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = Packaging::show($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 16-03-2021
      Description : Menyimpan daftar item pada inspeksi kontainer
      Developer : Didin
      Status : Create
    */
    public function showNewItem($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = PackagingNewItem::index($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 16-03-2021
      Description : Menyimpan daftar item pada inspeksi kontainer
      Developer : Didin
      Status : Create
    */
    public function showOldItem($id)
    {
        $status_code = 200;
        $msg = 'OK';
        try {
            $dt = PackagingOldItem::index($id);
            $data['data'] = $dt;
        } catch(Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 17-03-2021
      Description : Update data pada inspeksi kontainer
      Developer : Didin
      Status : Create
    */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required'
        ]);

        $status_code = 200;
        $msg = 'OK';
        DB::beginTransaction();
        try {
            Packaging::update($request->all(), $id);
            PackagingOldItem::storeMultiple($request->old_items, $id);
            PackagingNewItem::storeMultiple($request->new_items, $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 17-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            Packaging::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /*
      Date : 17-03-2021
      Description : Approve data
      Developer : Didin
      Status : Create
    */
    public function approve($id)
    {
        $status_code = 200;
        $msg = 'Data successfully approved';
        DB::beginTransaction();
        try {
            Packaging::approve($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }
}

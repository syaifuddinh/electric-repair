<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Operational\ManifestDetail;
use Illuminate\Http\Request;

class StuffingDetail
{
    public static function index($manifest_id) {
        Manifest::validate($manifest_id, 'picking_order');
        $dt = ManifestDetail::index($manifest_id);

        return $dt;
    }

    public static function store(Request $request, $manifest_id) {
        Manifest::validate($manifest_id, 'picking_order');
        $dt = ManifestDetail::store($request, $manifest_id);
    }

    public static function destroy($id) {
        $dt = ManifestDetail::destroy($id);
    }
}

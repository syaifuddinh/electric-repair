<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\WarehouseStockDetail;
use Response;

class CheckExistingTTB
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(isset($request->no_surat_jalan)) {
            if(WarehouseStockDetail::whereNoSuratJalan($request->no_surat_jalan)->count() == 0) {
                return Response::json(['message' => 'No TTB ' . $request->no_surat_jalan . ' tidak ditemukan'], 422);
            }
        }

        if($request->has('detail')) {
            $detail = is_string($request->detail) ? json_decode($request->detail) : $request->detail;
            $detail = collect($detail);
            $detail->each(function($value, $key) {
                if(isset($value['no_surat_jalan'])) {
                    if(WarehouseStockDetail::whereNoSuratJalan($value['no_surat_jalan'])->count() == 0) {
                        return Response::json(['message' => 'No TTB ' .$value['no_surat_jalan']  . ' tidak ditemukan'], 422);
                    }
                }
            });


        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Model\Item;
use App\Model\Warehouse;
use App\Model\Contact;
use App\Model\Company;
use App\Model\VehicleType;
use Response;
use DB;
use Closure;

class CheckExistingMaster
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
        if( $request->filled('item_id') ) {
            $i = Item::find($request->item_id);
            if( $i == null ) {
                return Response::json(['message' => 'ID Barang tidak ditemukan'], 422);
            }
            else {
                $request->item_code = $i->code;
                $request->item_name = $i->name;
            }
        } 

        if( $request->filled('warehouse_id') ) {
            if( Warehouse::find($request->warehouse_id) == null ) {
                return Response::json(['message' => 'ID Gudang tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('receipt_type_id') ) {
            $exist = DB::table('receipt_types')
            ->whereId($request->receipt_type_id)
            ->first();
            if( $exist == null ) {
                return Response::json(['message' => 'ID Tipe penerimaan barang tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('purchase_order_id') ) {
            $exist = DB::table('purchase_orders')
            ->whereId($request->purchase_order_id)
            ->first();
            if( $exist == null ) {
                return Response::json(['message' => 'ID Purchase order tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('warehouse_receipt_id') ) {
            if( DB::table('warehouse_receipts')->whereId($request->warehouse_receipt_id)->first() == null ) {
                return Response::json(['message' => 'ID Penerimaan barang tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('vehicle_type_id') ) {
            if( VehicleType::find($request->vehicle_type_id) == null ) {
                return Response::json(['message' => 'ID Kendaraan tidak ditemukan'], 422);
            }            
        } 
        if( $request->filled('warehouse_from_id') ) {
            if( Warehouse::find($request->warehouse_from_id) == null ) {
                return Response::json(['message' => 'ID Gudang Asal tidak ditemukan'], 422);
            }            
        } 
        if( $request->filled('warehouse_to_id') ) {
            if( Warehouse::find($request->warehouse_to_id) == null ) {
                return Response::json(['message' => 'ID Gudang Tujuan tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('customer_id') ) {
            if( Contact::find($request->customer_id) == null ) {
                return Response::json(['message' => 'ID Customer tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('staff_gudang_id') ) {
            if( Contact::find($request->staff_gudang_id) == null ) {
                return Response::json(['message' => 'ID Staff Gudang tidak ditemukan'], 422);
            }            
        } 

        if( $request->filled('company_id') ) {
            if( Company::find($request->company_id) == null ) {
                return Response::json(['message' => 'ID Cabang tidak ditemukan'], 422);
            }        
        }

        $imposition = collect([1, 2, 3 ,4]);
        if( $request->filled('imposition') ) {
            $is_exist = $imposition->contains($request->imposition); 

            if($is_exist == false) {
                return Response::json(['message' => 'ID Pengenaan tidak ditemukan'], 422);
            }
        }

        if($request->has('detail')) {
            $detail = is_string($request->detail) ? json_decode($request->detail) : $request->detail;

            $detail = collect($detail);
            $invalid_imposition = $detail->filter(function($value, $key) use ( $imposition) {
                // return isset($value['imposition']) && !$imposition->contains($value['imposition']); 
                return isset($value->imposition) && !$imposition->contains($value->imposition);
            });

            if($invalid_imposition->count() > 0) {
                return Response::json(['message' => 'ID Pengenaan tidak ditemukan pada detail barang'], 422);
            }


        }
        return $next($request);
    }
}

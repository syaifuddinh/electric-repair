<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PriceListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service_id' => 'required',
            'company_id' => 'required',
            'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
            'commodity_id' => 'required_if:stype_id,5',
            'code' => 'required|unique:price_lists',
            'name' => 'required',
            'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
            'moda_id' => 'required_if:stype_id,1',
            'vehicle_type_id' => 'required_if:stype_id,3|required_if:stype_id,4',
            "min_tonase" => 'required_if:stype_id,1|integer',
            "price_tonase" => 'required_if:stype_id,1|required_if:stype_id,5|integer',
            "min_volume" => 'required_if:stype_id,1|integer',
            "price_volume" => 'required_if:stype_id,1|required_if:stype_id,5|integer',
            "min_item" => 'required_if:stype_id,1|integer',
            "price_item" => 'required_if:stype_id,1|integer',
            "price_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7|integer',
            'price_handling_tonase' => 'integer',
            'price_handling_volume' => 'integer',
            'container_type_id' => 'required_if:stype_id,2',
            'warehouse_id' => 'required_if:stype_id,5',
            'service_id' => 'required_if:is_warehouse,0|required_if:is_warehouse,1',
            'combined_price_id' => 'required_if:is_warehouse,2',
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'Cabang tidak boleh kosong',
            'code.required' => 'Kode tidak boleh kosong',
            'code.unique' => 'Kode sudah pernah dipakai',
            'name.required' => 'Nama tidak boleh kosong',
            'service_id.required' => 'Layanan tidak boleh kosong',
            'route_id.required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4' => 'Trayek tidak boleh kosong jika tipe layanan adalah pengiriman FCL / pengiriman LCL / pengiriman per trip / transportasi',
            'container_type_id.required_if:stype_id,2' => 'Tipe kontainer tidak boleh kosong jika tipe layanan adalah pengiriman LCL',
            'piece_id.required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7' => 'Satuan tidak boleh kosong jika tipe layanan adalah transportasi / jasa kepabeanan / jasa lainnya',
            'service_id.required_if' => 'Layanan tidak boleh kosong jika jenis layanan adalah operational atau warehouse',
            'combined_price_id.required_if' => 'Paket tidak boleh kosong'
        ];
    }
}

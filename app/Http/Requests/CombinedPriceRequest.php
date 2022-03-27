<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CombinedPriceRequest extends FormRequest
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
            'company_id' => 'required',
            'code' => 'required',
            'name' => 'required',
            'detail' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'Cabang tidak boleh kosong',
            'code.required' => 'Kode tidak boleh kosong',
            'name.required' => 'Nama tidak boleh kosong',
            'detail.required' => 'Detail tidak boleh kosong'
        ];
    }
}

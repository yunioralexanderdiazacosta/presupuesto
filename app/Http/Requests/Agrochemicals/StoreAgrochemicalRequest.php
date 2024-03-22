<?php

namespace App\Http\Requests\Agrochemicals;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgrochemicalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cc' => 'required',
            'subfamily_id' => 'required',
            'products' => 'required|array',
            'products.*.product_name' => 'required',
            'products.*.unit_id' => 'required',
            'products.*.unit_id_price' => 'required',
            'products.*.dose' => 'required',
            'products.*.mojamiento' => 'required',
            'products.*.price' => 'required',
            'products.*.months' => 'required',
            'products.*.dose_type_id' => 'required'
        ];
    }
}

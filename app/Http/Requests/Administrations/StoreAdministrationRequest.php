<?php

namespace App\Http\Requests\Administrations;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrationRequest extends FormRequest
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
            'subfamily_id' => 'required',
            'products' => 'required|array',
            'products.*.product_name' => 'required',
            'products.*.unit_id' => 'required',
            'products.*.price' => 'required',
            'products.*.months' => 'required'
        ];
    }
}

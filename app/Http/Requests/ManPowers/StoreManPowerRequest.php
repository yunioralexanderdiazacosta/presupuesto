<?php

namespace App\Http\Requests\ManPowers;

use Illuminate\Foundation\Http\FormRequest;

class StoreManPowerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'cc' => 'required',
            'subfamily_id' => 'required',
            'products.*.product_name' => 'required',
            'products.*.workday' => 'required',
            'products.*.price' => 'required'
        ];
    }
}

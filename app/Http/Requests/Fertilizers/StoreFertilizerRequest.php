<?php

namespace App\Http\Requests\Fertilizers;

use Illuminate\Foundation\Http\FormRequest;

class StoreFertilizerRequest extends FormRequest
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
            'products.*.product_name' => 'required',
            'products.*.unit_id' => 'required',
            'products.*.dose' => 'required',
            'products.*.price' => 'required'
        ];
    }
}

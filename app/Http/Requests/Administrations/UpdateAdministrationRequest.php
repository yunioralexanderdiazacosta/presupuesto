<?php

namespace App\Http\Requests\Administrations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdministrationRequest extends FormRequest
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
            'product_name' => 'required',
            'price' => 'required',
            'subfamily_id' => 'required',
            'unit_id' => 'required'
        ];
    }
}

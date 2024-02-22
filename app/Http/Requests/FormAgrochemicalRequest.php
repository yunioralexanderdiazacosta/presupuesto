<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormAgrochemicalRequest extends FormRequest
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
            'dose_type' => 'required',
            'dose' => 'required',
            'price' => 'required',
            'mojamiento' => 'required',
            'subfamily_id' => 'required',
            'unit_id' => 'required',
        ];
    }
}

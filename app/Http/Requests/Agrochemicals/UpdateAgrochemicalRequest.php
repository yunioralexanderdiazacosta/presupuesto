<?php

namespace App\Http\Requests\Agrochemicals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgrochemicalRequest extends FormRequest
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
            'dose' => 'required',
            'price' => 'required',
            'mojamiento' => 'required',
            'subfamily_id' => 'required',
            'unit_id' => 'required',
            'unit_id_price' => 'required',
            'dose_type_id' => 'required'
        ];
    }
}

<?php

namespace App\Http\Requests\ManPowers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManPowerRequest extends FormRequest
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
            'workday' => 'required',
            'price' => 'required',
            'subfamily_id' => 'required',
        ];
    }
}

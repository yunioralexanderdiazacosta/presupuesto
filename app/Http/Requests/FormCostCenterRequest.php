<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormCostCenterRequest extends FormRequest
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
            'name' => 'required',
            'surface' => 'required',
            'fruit_id' => 'required',
            'variety_id' => 'required',
            'parcel_id' => 'required',
            'development_state_id' => 'required',
            'year_plantation' => 'required'
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormMachineryRequest extends FormRequest
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
            'company_reason_id' => 'required', 
            'type_machinery_id' => 'required',
            'cod_machinery' => 'required', 
            'volume' => 'required',
            'brand' => 'required',
            'is_active' => 'required'
        ];
    }
}

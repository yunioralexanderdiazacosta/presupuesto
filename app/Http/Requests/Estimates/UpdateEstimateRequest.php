<?php

namespace App\Http\Requests\Estimates;


use Illuminate\Foundation\Http\FormRequest;

class UpdateEstimateRequest extends FormRequest
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
            'estimate_status_id' => 'required|exists:estimate_status,id',
            'kilos_ha' => 'required|integer|min:0',
            'cost_center_variety_id' => 'required|exists:cost_center_varieties,id',
            'observations' => 'nullable|string'
        ];
    }
}

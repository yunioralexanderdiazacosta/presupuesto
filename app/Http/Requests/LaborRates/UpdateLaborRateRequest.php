<?php

namespace App\Http\Requests\LaborRates;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaborRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'rate' => 'required|integer|min:0',
            'labor_type_id' => 'nullable|exists:labor_types,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del trato es obligatorio.',
            'rate.required' => 'El monto del trato es obligatorio.',
            'rate.min' => 'El valor del trato debe ser mayor o igual a 0.',
        ];
    }
}
